#!/usr/bin/env python
#  -*- coding:utf-8 -*-
# File add_nginx_rule.py

import time
import sys
import os
import commands


domain=sys.argv[1]
env=sys.argv[2]
text='''
location /%s/{
if ($uri = '/%s/' ) {
rewrite  .  /%s/首页 permanent;
}
if ($uri != '/%s/' ) {
rewrite /%s/(.*)$   /$1  break;
proxy_pass    http://%s.joyme.%s;
}
}
''' %(domain,domain,domain,domain,domain,domain,env)

text2='''
location /%s/{
if ($uri = '/%s/' ) {
rewrite  .  /%s/首页 permanent;
}
proxy_set_header  wikitype mwiki;
if ($uri != '/%s/' ) {
rewrite /%s/(.*)$   /$1  break;
proxy_pass    http://%s.joyme.%s;
}
}
''' %(domain,domain,domain,domain,domain,domain,env)


#curtime=time.strftime('%Y-%m-%d-%H:%M',time.localtime(time.time()))
#subprocess.Popen('cp rule_ugc_rules rule_ugc_rules_%s' %curtime,shell=True,stdout=subprocess.PIPE,stderr=subprocess.STDOUT)
#os.popen('cp rule_ugc_rules rule_ugc_rules_%s' %curtime)

if env == "beta":
   file=open('/usr/local/nginx/conf/vhost/rule_ugc_rules','a')
   file.write(text)
   file.close()

   file2=open('/usr/local/nginx/conf/vhost/rule_ugc_rules_mobile','a')
   file2.write(text2)
   file2.close()
else:
   file=open('/usr/local/nginx/conf/rule_ugc_rules','a')
   file.write(text)
   file.close()

   file2=open('/usr/local/nginx/conf/rule_ugc_rules_mobile','a')
   file2.write(text2)
   file2.close()
   
(status,output)=commands.getstatusoutput('/usr/bin/sudo /usr/local/nginx/sbin/nginx -t -c /usr/local/nginx/conf/nginx.conf')

print status
