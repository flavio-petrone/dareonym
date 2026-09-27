"""Exercise production HTTPS enforcement through php-cgi, without a database.
Requires php-cgi on PATH. Uses a disposable copy and no real hosting credentials.
"""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='dareonym-transport-') as temp:
    app = Path(temp)
    (app / 'app').mkdir()
    (app / 'config').mkdir()
    (app / 'storage/logs').mkdir(parents=True)
    shutil.copy(root / 'app/core.php', app / 'app/core.php')
    (app / 'probe.php').write_text('<?php require __DIR__."/app/core.php"; echo "SECURE_OK";')
    def request(method='GET', secure=False, forwarded=None):
        env = dict(os.environ, REDIRECT_STATUS='200', SCRIPT_FILENAME=str(app / 'probe.php'),
                   REQUEST_METHOD=method, HTTP_HOST='example.test', REQUEST_URI='/dareonym2/probe.php?test=1',
                   SERVER_PROTOCOL='HTTP/1.1', CONTENT_LENGTH='0', HTTPS='on' if secure else 'off')
        env.pop('HTTP_X_FORWARDED_PROTO', None)
        if forwarded is not None:
            env['HTTP_X_FORWARDED_PROTO'] = forwarded
        return subprocess.run(['php-cgi'], env=env, input='', text=True, capture_output=True, check=True).stdout
    r = request()
    assert 'Status: 302' in r and 'Location: https://example.test/dareonym2/probe.php?test=1' in r
    assert 'Set-Cookie:' not in r and 'SECURE_OK' not in r
    print('PASS: HTTP GET redirects before session creation')
    r = request('POST')
    assert 'Status: 400' in r and 'Location:' not in r and 'Set-Cookie:' not in r
    print('PASS: HTTP POST rejected without replay or session creation')
    r = request(secure=True)
    assert 'SECURE_OK' in r and 'secure;' in r.lower()
    print('PASS: HTTPS creates Secure session and reaches application')
    r = request(forwarded='https')
    assert 'Status: 302' in r and 'SECURE_OK' not in r
    print('PASS: Untrusted forwarding header does not bypass HTTPS')
    (app / 'config/https-proxy.php').write_text('<?php return true;')
    r = request(forwarded='https')
    assert 'SECURE_OK' in r and 'secure;' in r.lower()
    print('PASS: Explicit trusted proxy supports HTTPS and Secure cookies')
    r = request(forwarded='http')
    assert 'Status: 302' in r and 'Set-Cookie:' not in r
    print('PASS: HTTP through trusted proxy still redirects')
print('6 production transport checks passed.')
