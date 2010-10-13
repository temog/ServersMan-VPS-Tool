#!/usr/bin/env python
# -*- coding: utf-8 -*-
import sys, urllib, urllib2, cookielib, commands, datetime

#### 設定開始 #########################################

# ServersMan管理ツール
smAdminParam = {
    'passwd'   : '管理ツールのパスワード',
    'usr_name' : 'admin',
    'q'        : 'auth',
}

# ServersManのIDとパスワード
smAccParam = {
    'sm-account'  : 'ServersManのメールアドレス',
    'sm-password' : 'ServersManのパスワード',
    'q'           : 4,
    'setSMState'  : 0,
    'smSwitch'    : 1,
    'B1'          : '設定'
}

#### 設定終了 #########################################

# ServersManのURL
smUrl = 'http://localhost/serversman'

# プロセス監視するコマンド
chkstr = 'ps -ef | grep "/opt/serversman/bin/serversman" | grep -v "grep"'


result = commands.getoutput(chkstr)
if result:
    sys.exit();


cookie = cookielib.CookieJar()
opener = urllib2.build_opener(urllib2.HTTPCookieProcessor(cookie))

# トップページでログイン
try:
    result = opener.open(
        smUrl,
        urllib.urlencode(smAdminParam)
    )
except:
    print sys.exc_info()
    sys.exit()

# 設定・変更ページからServersManを「有効」にする
try:
    result = opener.open(
        smUrl,
        urllib.urlencode(smAccParam)
    )
except:
    print sys.exc_info()
    sys.exit()

# リスタートした日時を出力
now = datetime.datetime.now()
print now.strftime("%Y-%m-%d %H:%M:%S")

