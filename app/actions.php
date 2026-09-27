<?php

function handle_action(): void
{
    $action = field('action', 40);
    if ($action === 'login') {
        $email = strtolower(field('email', 190));
        $password = field('password', 72);
        // Both the account and the source IP have persistent, independent rate limits.
        $buckets = [hash_hmac('sha256', 'email:'.$email, config()['key']),hash_hmac('sha256', 'ip:'.($_SERVER['REMOTE_ADDR'] ?? ''), config()['key'])];
        sql('DELETE FROM d2_attempts WHERE expires_at<?', [time()]);
        foreach ($buckets as $bucket) {
            $attempt = one('SELECT * FROM d2_attempts WHERE bucket=?', [$bucket]);
            if ($attempt && (int)$attempt['attempts'] >= 10) {
                abort_request(429, 'Troppi tentativi. Attendi 15 minuti e riprova.');
            }
        }
        $u = one('SELECT * FROM d2_users WHERE email=? AND active=1', [$email]);
        $valid = $u && password_verify($password, $u['password']);
        if (!$valid) {
            foreach ($buckets as $bucket) {
                if (db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                    sql('INSERT INTO d2_attempts (bucket,attempts,expires_at) VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=attempts+1', [$bucket,time() + 900]);
                } else {
                    sql('INSERT INTO d2_attempts (bucket,attempts,expires_at) VALUES (?,1,?) ON CONFLICT(bucket) DO UPDATE SET attempts=attempts+1', [$bucket,time() + 900]);
                }
            }
            flash('Email o password non corrette.', 'error');
            go('login');
        }
        session_regenerate_id(true);
        $_SESSION = ['uid' => $u['id'],'auth_version' => $u['auth_version'],'csrf' => bin2hex(random_bytes(32))];
        sql('DELETE FROM d2_attempts WHERE bucket=?', [$buckets[0]]);
        if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
            sql('UPDATE d2_users SET password=? WHERE id=?', [password_hash($password, PASSWORD_DEFAULT),$u['id']]);
        }
        go();
    }
    $u = require_user();
    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        go('login');
    }
    if ($action === 'account_create') {
        require_admin();
        $name = field('name', 80);
        $email = email_field();
        $role = field('role', 16);
        if (!in_array($role, ['admin','creator','user'], true)) {
            abort_request(422, 'Ruolo non valido.');
        }
        if (one('SELECT id FROM d2_users WHERE email=?', [$email])) {
            abort_request(422, 'Esiste già un account con questa email.');
        }
        $hash = password_hash_new(field('password', 72));
        sql('INSERT INTO d2_users (name,email,password,role,created_at) VALUES (?,?,?,?,?)', [$name,$email,$hash,$role,date('Y-m-d H:i:s')]);
        audit('account.created', $email);
        flash('Account creato. Comunica le credenziali alla persona tramite un canale privato.');
        go('people');
    }
    if ($action === 'account_toggle') {
        require_admin();
        $id = id_field('id');
        if ($id === $u['id']) {
            abort_request(422, 'Non puoi disattivare il tuo account.');
        }
        $target = one('SELECT * FROM d2_users WHERE id=?', [$id]);
        if (!$target) {
            abort_request(404, 'Account non trovato.');
        }
        sql('UPDATE d2_users SET active=?,auth_version=auth_version+1 WHERE id=?', [(int)!$target['active'],$id]);
        audit('account.toggled', (string)$id);
        flash('Stato account aggiornato.');
        go('people');
    }
    if ($action === 'account_reset') {
        require_admin();
        $id = id_field('id');
        if (!one('SELECT id FROM d2_users WHERE id=?', [$id])) {
            abort_request(404, 'Account non trovato.');
        }
        sql('UPDATE d2_users SET password=?,auth_version=auth_version+1 WHERE id=?', [password_hash_new(field('password', 72)),$id]);
        audit('account.password_reset', (string)$id);
        flash('Password aggiornata.');
        go('people');
    }
    if ($action === 'profile') {
        $old = field('current_password', 72);
        $row = one('SELECT password FROM d2_users WHERE id=?', [$u['id']]);
        if (!password_verify($old, $row['password'])) {
            abort_request(422, 'La password attuale non è corretta.');
        }
        sql('UPDATE d2_users SET name=?,password=?,auth_version=auth_version+1 WHERE id=?', [field('name', 80),password_hash_new(field('password', 72)),$u['id']]);
        session_regenerate_id(true);
        $_SESSION['auth_version'] = $u['auth_version'] + 1;
        audit('profile.updated', (string)$u['id']);
        flash('Profilo aggiornato.');
        go('profile');
    }
    if ($action === 'group_create') {
        require_admin();
        $creator = id_field('creator_id');
        if (!one("SELECT id FROM d2_users WHERE id=? AND role='creator' AND active=1", [$creator])) {
            abort_request(422, 'Seleziona un creator attivo.');
        }
        sql('INSERT INTO d2_groups (name,description,creator_id,created_at) VALUES (?,?,?,?)', [field('name', 100),field('description', 1500, false),$creator,date('Y-m-d H:i:s')]);
        audit('group.created', (string)db()->lastInsertId());
        flash('Gruppo creato. Ora aggiungi i partecipanti.');
        go('groups');
    }
    if (in_array($action, ['member_add','member_remove'], true)) {
        $gid = id_field('group_id');
        manage_group($gid);
        $uid = id_field('user_id');
        if (!one("SELECT id FROM d2_users WHERE id=? AND role='user'", [$uid])) {
            abort_request(422, 'Seleziona un partecipante.');
        }
        if ($action === 'member_add') {
            if (!one('SELECT user_id FROM d2_memberships WHERE group_id=? AND user_id=?', [$gid,$uid])) {
                sql('INSERT INTO d2_memberships (group_id,user_id) VALUES (?,?)', [$gid,$uid]);
            }
        } else {
            sql('DELETE FROM d2_memberships WHERE group_id=? AND user_id=?', [$gid,$uid]);
        }
        audit($action, (string)$gid);
        flash('Partecipanti aggiornati.');
        go('group', ['id' => $gid]);
    }
    if ($action === 'challenge_create') {
        $gid = id_field('group_id');
        manage_group($gid);
        $points = id_field('points');
        if ($points > 1000) {
            abort_request(422, 'Puoi assegnare da 1 a 1000 punti.');
        }
        $due = field('due_date', 10, false);
        if ($due !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $due) || date('Y-m-d', strtotime($due)) !== $due || $due < date('Y-m-d'))) {
            abort_request(422, 'Scegli una scadenza valida da oggi in avanti.');
        }
        sql('INSERT INTO d2_challenges (group_id,title,description,points,due_date,created_at) VALUES (?,?,?,?,?,?)', [$gid,field('title', 140),field('description', 5000),$points,$due ?: null,date('Y-m-d H:i:s')]);
        $id = (int)db()->lastInsertId();
        audit('challenge.created', (string)$id);
        flash('La nuova sfida è disponibile per il gruppo.');
        go('challenge', ['id' => $id]);
    }
    if ($action === 'challenge_toggle') {
        $c = challenge(id_field('id'));
        manage_group((int)$c['group_id']);
        sql('UPDATE d2_challenges SET archived=? WHERE id=?', [(int)!$c['archived'],$c['id']]);
        audit('challenge.toggled', (string)$c['id']);
        flash('Stato della sfida aggiornato.');
        go('challenge', ['id' => $c['id']]);
    }
    if ($action === 'submit') {
        if ($u['role'] !== 'user') {
            abort_request(403, 'Solo i partecipanti possono inviare una prova.');
        }
        $c = challenge(id_field('challenge_id'));
        if ($c['archived'] || ($c['due_date'] && $c['due_date'] < date('Y-m-d'))) {
            abort_request(422, 'Questa sfida è conclusa.');
        }
        $note = field('note', 2000);
        $old = one('SELECT * FROM d2_submissions WHERE challenge_id=? AND user_id=?', [$c['id'],$u['id']]);
        if ($old && $old['status'] !== 'rejected') {
            abort_request(409, 'Hai già inviato una prova per questa sfida.');
        }
        $proof = upload_proof($_FILES['proof'] ?? [], $old !== null);
        try {
            db()->beginTransaction();
            if ($old) {
                $changed = sql("UPDATE d2_submissions SET note=?,proof=?,status='pending',feedback='',awarded_points=0,reviewed_by=NULL,submitted_at=?,reviewed_at=NULL WHERE id=? AND status='rejected'", [$note,$proof,date('Y-m-d H:i:s'),$old['id']]);
                if ($changed->rowCount() !== 1) {
                    abort_request(409, 'La prova è stata già aggiornata.');
                }
            } else {
                sql("INSERT INTO d2_submissions (challenge_id,user_id,note,proof,status,feedback,submitted_at) VALUES (?,?,?,?,'pending','',?)", [$c['id'],$u['id'],$note,$proof,date('Y-m-d H:i:s')]);
            }
            audit('proof.submitted', (string)$c['id']);
            db()->commit();
        } catch (Throwable $err) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }delete_proof($proof);
            throw $err;
        }
        if ($old) {
            delete_proof($old['proof']);
        }flash('Prova inviata. Il creator la valuterà e ti lascerà un riscontro.');
        go('challenge', ['id' => $c['id']]);
    }
    if ($action === 'review') {
        $s = one('SELECT s.*,c.group_id,c.points FROM d2_submissions s JOIN d2_challenges c ON c.id=s.challenge_id WHERE s.id=?', [id_field('id')]);
        if (!$s) {
            abort_request(404, 'Prova non trovata.');
        }manage_group((int)$s['group_id']);
        $decision = field('decision', 16);
        if (!in_array($decision, ['approved','rejected'], true)) {
            abort_request(422, 'Valutazione non valida.');
        }
        $feedback = field('feedback', 2000, $decision === 'rejected');
        db()->beginTransaction();
        $changed = sql("UPDATE d2_submissions SET status=?,feedback=?,awarded_points=?,reviewed_by=?,reviewed_at=? WHERE id=? AND status='pending'", [$decision,$feedback,$decision === 'approved' ? $s['points'] : 0,$u['id'],date('Y-m-d H:i:s'),$s['id']]);
        if ($changed->rowCount() !== 1) {
            abort_request(409, 'Questa prova è già stata valutata.');
        }audit('proof.'.$decision, (string)$s['id']);
        db()->commit();
        flash($decision === 'approved' ? 'Prova approvata. Punti assegnati una sola volta.' : 'Riscontro inviato. Il partecipante potrà riprovare entro la scadenza.');
        go('reviews');
    }
    abort_request(400, 'Operazione non riconosciuta.');
}
