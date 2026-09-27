"""Test web installation on an EMPTY MySQL database named dareonym_test_*.
Usage: python3 tests/installer.py /private/test-db.json
Creates test tables; does not drop anything. Uses a disposable application copy.
"""
import http.cookiejar
import json
import os
from pathlib import Path
import re
import secrets
import shutil
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

root = Path(__file__).resolve().parents[1]
cfg = json.loads(Path(sys.argv[1]).read_text())
assert cfg['database'].startswith('dareonym_test_') and cfg['driver'] == 'mysql'
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs): return None
count = 0
def check(condition,label):
    global count
    if not condition: raise AssertionError(label)
    count += 1
    print('PASS:',label)
with tempfile.TemporaryDirectory(prefix='dareonym-install-') as tmp:
    app=Path(tmp)/'app'
    shutil.copytree(root,app,ignore=shutil.ignore_patterns('local.php','install.php','*.sqlite','*.log'))
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0));port=sock.getsockname()[1]
    log=open(Path(tmp)/'server.log','w+')
    server=subprocess.Popen(['php','-S',f'127.0.0.1:{port}','router.php'],cwd=app,stdout=log,stderr=log)
    client=urllib.request.build_opener(NoRedirect,urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def request(fields=None,path='/'):
        try:r=client.open(urllib.request.Request(f'http://127.0.0.1:{port}'+path,urllib.parse.urlencode(fields).encode() if fields else None))
        except urllib.error.HTTPError as err:r=err
        return r.code,dict(r.headers),r.read()
    try:
        for _ in range(60):
            try:request();break
            except OSError:time.sleep(.05)
        check(b'install.example.php' in request()[2], 'installer closed without private code')
        token=secrets.token_hex(24)
        (app/'config/install.php').write_text("<?php return '"+token+"';")
        data=request()[2]
        csrf=re.search(rb'name="csrf" value="([a-f0-9]+)"',data)[1].decode()
        fields={'csrf':csrf,'install_code':'wrong','name':'Admin Test','email':'setup@example.test','password':'Setup-test-password!','db_host':cfg['host'],'db_port':cfg['port'],'db_name':cfg['database'],'db_user':cfg['username'],'db_password':cfg['password']}
        check(request(fields)[0] == 403, 'incorrect installation code rejected')
        fields['install_code']=token
        status,headers,body=request(fields)
        check(status == 303 and 'login' in headers.get('Location',''), 'web setup creates schema and admin')
        check((app/'config/local.php').is_file(), 'private configuration written')
        check(not (app/'config/install.php').exists(), 'one-time code removed')
        check(request(path='/config/local.php')[0] == 403, 'configuration cannot be downloaded')
        data=request(path='/index.php?page=login')[2]
        csrf=re.search(rb'name="csrf" value="([a-f0-9]+)"',data)[1].decode()
        status,headers,body=request({'csrf':csrf,'action':'login','email':'setup@example.test','password':'Setup-test-password!'},'/index.php?page=login')
        check(status == 303 and 'dashboard' in headers.get('Location',''), 'newly created admin can log in')
        check(request(path='/index.php?page=dashboard')[0] == 200, 'fresh dashboard works with zero data')
        check(b'install_code' not in request()[2], 'installer unavailable after setup')
        print(f'{count} installer checks passed on MySQL/MariaDB.')
    finally:
        server.terminate();server.wait(timeout=10);log.close()
