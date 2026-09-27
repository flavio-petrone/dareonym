"""Integration tests against disposable SQLite or an empty MySQL test database.
Run: python3 tests/run.py [--mysql-config /private/test-config.json]
The optional JSON must name an EMPTY database starting with dareonym_test_.
No schema is dropped automatically. All application files are copied to a temp directory.
"""
import argparse
import base64
import http.cookiejar
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument('--mysql-config')
args = parser.parse_args()
mysql = json.loads(Path(args.mysql_config).read_text()) if args.mysql_config else None
if mysql and (mysql.get('driver') != 'mysql' or not mysql.get('database', '').startswith('dareonym_test_')):
    raise SystemExit('Refusing a non-test database')
checks = 0

def check(value, label):
    global checks
    if not value:
        raise AssertionError(label)
    checks += 1
    print('PASS:', label)

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None

with tempfile.TemporaryDirectory(prefix='dareonym2-tests-') as temp:
    app = Path(temp) / 'app'
    shutil.copytree(ROOT, app, ignore=shutil.ignore_patterns('local.php', 'install.php', '*.sqlite', '*.log'))
    demo = app / 'scripts/demo.php'
    if mysql:
        private = Path(temp) / 'db.json'
        private.write_text(json.dumps(mysql))
        env = dict(os.environ, DAREONYM_DEMO_MYSQL_CONFIG=str(private))
    else:
        env = os.environ.copy()
    subprocess.run(['php', str(demo)], env=env, check=True, capture_output=True)
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    log = open(Path(temp) / 'server.log', 'w+')
    server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', 'router.php'], cwd=app, stdout=log, stderr=log)
    base = f'http://127.0.0.1:{port}'
    def client():
        return urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def request(c, path='/', fields=None, multipart=None):
        headers = {}
        if multipart:
            body, boundary = multipart
            headers['Content-Type'] = 'multipart/form-data; boundary=' + boundary
        else:
            body = urllib.parse.urlencode(fields).encode() if fields is not None else None
        req = urllib.request.Request(base + path, body, headers)
        try:
            r = c.open(req)
        except urllib.error.HTTPError as err:
            r = err
        return r.code, dict(r.headers), r.read()
    def token(c, page='login'):
        status, _, body = request(c, '/index.php?page=' + page)
        if status != 200:
            raise AssertionError(f'Token page {page}: {status} {body[:200]!r}')
        return re.search(rb'name="csrf" value="([a-f0-9]+)"', body)[1].decode()
    def login(email, password='Local-demo-2026!'):
        c = client()
        r = request(c, '/index.php?page=login', {'csrf': token(c), 'action': 'login', 'email': email, 'password': password})
        check(r[0] == 303 and 'dashboard' in r[1].get('Location', ''), 'login ' + email)
        return c
    def post(c, action, fields=None):
        values = {'csrf': token(c, 'profile'), 'action': action}
        values.update(fields or {})
        return request(c, '/index.php', values)
    def query(query, params=None):
        code = 'require $argv[1]."/app/core.php"; echo json_encode(all($argv[2],json_decode($argv[3],true)));'
        r = subprocess.run(['php', '-r', code, str(app), query, json.dumps(params or [])], capture_output=True, text=True, check=True)
        return json.loads(r.stdout)
    def upload(c, challenge=1, data=None, note='Una prova di test', filename='proof.png'):
        boundary = 'DareonymIntegrationBoundary'
        values = {'csrf': token(c, 'profile'), 'action': 'submit', 'challenge_id': str(challenge), 'note': note}
        body = b''
        for key, value in values.items():
            body += f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{value}\r\n'.encode()
        body += f'--{boundary}\r\nContent-Disposition: form-data; name="proof"; filename="{filename}"\r\nContent-Type: image/png\r\n\r\n'.encode()
        body += (png if data is None else data) + f'\r\n--{boundary}--\r\n'.encode()
        return request(c, '/index.php', multipart=(body, boundary))
    png = subprocess.check_output(['php', '-r', '$i=imagecreatetruecolor(20,20);imagefill($i,0,0,imagecolorallocate($i,120,150,90));imagepng($i);'])
    try:
        anon = client()
        for _ in range(60):
            try:
                request(anon)
                break
            except OSError:
                time.sleep(.05)
        check(request(anon)[0] == 303, 'anonymous dashboard redirects')
        check(request(anon, '/index.php?page=login')[0] == 200, 'login renders')
        check(request(anon, '/index.php', {'action': 'login'})[0] == 403, 'missing CSRF blocked')
        admin = login('admin@example.test')
        creator = login('creator@example.test')
        user = login('user@example.test')
        other = login('other@example.test')
        othercreator = login('othercreator@example.test')
        for c, pages in [(admin,['dashboard','challenges','groups','people','reviews','leaderboard','profile','new-challenge']), (creator,['dashboard','challenges','groups','reviews','leaderboard','profile','new-challenge']), (user,['dashboard','challenges','groups','history','leaderboard','profile'])]:
            for page in pages:
                check(request(c, '/index.php?page=' + page)[0] == 200, 'render ' + page)
        check(request(user, '/index.php?page=people')[0] == 403, 'user cannot see accounts')
        check(request(creator, '/index.php?page=people')[0] == 403, 'creator cannot see accounts')
        check(request(user, '/index.php?page=challenge&id=4')[0] == 404, 'other group challenge hidden from user')
        check(request(creator, '/index.php?page=challenge&id=4')[0] == 404, 'other creator challenge hidden')
        check(post(creator, 'challenge_toggle', {'id':4})[0] == 404, 'creator cannot close another group challenge')
        check(post(user, 'challenge_create', {'group_id':1})[0] == 404, 'user cannot create challenges')
        check(post(admin,'account_create',{'name':"Test <script>alert(1)</script>",'email':'new@example.test','role':'user','password':'Strong-test-password!'})[0] == 303, 'admin creates account')
        people = request(admin, '/index.php?page=people')[2]
        check(b'&lt;script&gt;alert(1)&lt;/script&gt;' in people and b'Test <script>' not in people, 'account output escaped')
        check(post(admin,'account_create',{'name':'Duplicate','email':'new@example.test','role':'user','password':'Strong-test-password!'})[0] == 422, 'duplicate email rejected')
        check(post(admin,'group_create',{'name':"Un gruppo d'esempio",'description':'Test','creator_id':2})[0] == 303, 'group created with apostrophe')
        gid = query('SELECT MAX(id) id FROM d2_groups')[0]['id']
        check(post(creator,'member_add',{'group_id':gid,'user_id':3})[0] == 303, 'creator adds member')
        check(post(creator,'member_add',{'group_id':gid,'user_id':3})[0] == 303, 'duplicate membership is idempotent')
        check(post(creator,'challenge_create',{'group_id':gid,'title':"Impara l'arte",'description':'Una prova concreta','points':75,'due_date':''})[0] == 303, 'creator creates challenge')
        cid = query('SELECT MAX(id) id FROM d2_challenges')[0]['id']
        check(upload(user,cid,data=b'<?php echo "bad"; ?>')[0] == 422, 'fake image rejected')
        check(upload(user,cid,note="Ho creato un'opera <img src=x onerror=alert(1)>",filename='../../attack.php')[0] == 303, 'proof saved with generated name')
        submission = query('SELECT * FROM d2_submissions WHERE challenge_id=?',[cid])[0]
        sid = submission['id']
        check(bool(re.fullmatch(r'[a-f0-9]{48}\.jpg',submission['proof'])), 'filename generated and normalized')
        check(request(user,'/image.php?id='+str(sid))[0] == 200, 'owner reads proof')
        check(request(creator,'/image.php?id='+str(sid))[0] == 200, 'assigned creator reads proof')
        check(request(other,'/image.php?id='+str(sid))[0] == 404, 'other participant cannot read proof')
        check(request(othercreator,'/image.php?id='+str(sid))[0] == 404, 'other creator cannot read proof')
        check(upload(user,cid)[0] == 409, 'duplicate pending submission blocked')
        check(post(othercreator,'review',{'id':sid,'decision':'approved','feedback':''})[0] == 404, 'other creator cannot review')
        check(post(creator,'review',{'id':sid,'decision':'rejected','feedback':''})[0] == 422, 'rejection needs feedback')
        check(post(creator,'review',{'id':sid,'decision':'rejected','feedback':'Mostra meglio il risultato.'})[0] == 303, 'creator rejects with feedback')
        check(upload(user,cid,note='Ecco una prova migliore.')[0] == 303, 'rejected proof can be resubmitted')
        check(not (app/'storage/uploads'/submission['proof']).exists(), 'replaced image cleaned up')
        check(post(creator,'review',{'id':sid,'decision':'approved','feedback':'Ottimo lavoro!'})[0] == 303, 'creator approves proof')
        check(post(creator,'review',{'id':sid,'decision':'approved','feedback':''})[0] == 409, 'double approval blocked')
        check(query('SELECT awarded_points,status FROM d2_submissions WHERE id=?',[sid])[0] == {'awarded_points':75,'status':'approved'}, 'points awarded exactly once')
        check(b'75' in request(user,'/index.php?page=leaderboard')[2], 'leaderboard shows real score')
        check(post(creator,'challenge_toggle',{'id':cid})[0] == 303, 'challenge closed')
        check(upload(user,cid)[0] == 422, 'closed challenge rejects submission')
        check(post(creator,'member_remove',{'group_id':gid,'user_id':3})[0] == 303, 'member removed')
        check(request(user,f'/index.php?page=challenge&id={cid}')[0] == 404, 'removed member loses challenge access')
        check(query('SELECT awarded_points FROM d2_submissions WHERE id=?',[sid])[0]['awarded_points'] == 75, 'historical score preserved')
        for path in ['/config/local.php','/app/core.php','/storage/uploads/'+submission['proof'],'/tests/run.py','/.env.example','/scripts/demo.php']:
            check(request(anon,path)[0] in [403,404], 'private file blocked '+path)
        check(post(admin,'account_reset',{'id':3,'password':'New-test-password!'})[0] == 303, 'admin resets password')
        check(request(user,'/index.php?page=profile')[0] == 303, 'password reset invalidates old session')
        user = login('user@example.test','New-test-password!')
        check(post(user,'profile',{'name':'Luca Test','current_password':'New-test-password!','password':'Another-test-password!'})[0] == 303, 'participant changes profile and password')
        check(request(user,'/index.php?page=profile')[0] == 200, 'own password change keeps current session')
        check(post(admin,'account_toggle',{'id':3})[0] == 303, 'admin deactivates account')
        check(request(user,'/index.php?page=dashboard')[0] == 303, 'deactivated account session denied')
        check(post(admin,'account_toggle',{'id':1})[0] == 422, 'admin cannot disable self')
        check(post(creator,'logout')[0] == 303, 'logout works')
        check(request(creator,'/index.php?page=dashboard')[0] == 303, 'logout invalidates session')
        bad = client()
        for _ in range(10):
            request(bad,'/index.php?page=login',{'csrf':token(bad),'action':'login','email':'missing@example.test','password':'incorrect'})
        check(request(bad,'/index.php?page=login',{'csrf':token(bad),'action':'login','email':'missing@example.test','password':'incorrect'})[0] == 429, 'persistent login rate limit enforced')
        print(f'{checks} integration checks passed on {"MySQL/MariaDB" if mysql else "SQLite"}.')
    finally:
        server.terminate()
        server.wait(timeout=10)
        log.close()
