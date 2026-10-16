#!/usr/bin/env python3
"""Read-only deployed-site checks. Run with python3 tests/http-smoke.py URL."""
import concurrent.futures, json, re, sys, time, urllib.request, urllib.error
from html.parser import HTMLParser
base=(sys.argv[1] if len(sys.argv)>1 else 'https://kzfr.clydellmfactory.com').rstrip('/')
class Links(HTMLParser):
    def __init__(self): super().__init__(); self.links=[]
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='a' and a.get('href'): self.links.append(a['href'])
def get(path):
    start=time.perf_counter()
    try:
        with urllib.request.urlopen(urllib.request.Request(base+path,headers={'User-Agent':'KZFR-deployment-check/1.0'}),timeout=35) as response:
            body=response.read().decode('utf-8','replace')
            return {'path':path,'status':response.status,'headers':dict(response.headers),'seconds':round(time.perf_counter()-start,3),'bytes':len(body.encode()),'body':body}
    except urllib.error.HTTPError as error:
        return {'path':path,'status':error.code,'headers':dict(error.headers),'seconds':round(time.perf_counter()-start,3),'body':error.read().decode('utf-8','replace')}
paths=['/','/programs/','/programs/nativesong/','/schedule/?day=Monday','/archives/','/events/','/about/','/donate/','/underwrite/','/underwriters/','/volunteer/','/contact/','/demo-notes/','/programs/?q=Native+Song','/programs/?q=Shigemi','/programs/?q=zzzznotaprogramzzzz','/archives/?program=nativesong&date=2026-10-12','/archives/?genre=jazz','/events/?month=2026-11','/underwriters/?q=Chico','/robots.txt','/wp-json/wp/v2/users','/wp-config.php','/xmlrpc.php','/wp-content/uploads/test.php','/definitely-missing-demo-route/']
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool: results=list(pool.map(get,paths))
failures=[]
for r in results:
    path=r['path']; protected=path in ['/wp-json/wp/v2/users','/wp-config.php','/xmlrpc.php','/wp-content/uploads/test.php','/definitely-missing-demo-route/']
    if r['status'] != (404 if protected else 200): failures.append(f'{path}: unexpected HTTP {r["status"]}')
    headers={k.lower():v for k,v in r['headers'].items()}
    if 'noindex' not in headers.get('x-robots-tag',''): failures.append(f'{path}: missing noindex header')
    if not protected and path!='/robots.txt':
        if '<h1' not in r['body']: failures.append(f'{path}: missing h1')
        if 'WEBSITE PREVIEW' not in r['body']: failures.append(f'{path}: missing preview notice')
        if re.search(r'(Fatal error|Warning:|Parse error)',r['body']): failures.append(f'{path}: PHP error in output')
    if path=='/robots.txt' and 'Disallow: /' not in r['body']: failures.append('robots.txt does not disallow crawling')
lookup={r['path']:r['body'] for r in results}
for p,needle in [('/programs/?q=Native+Song','Native Song'),('/programs/?q=Shigemi','Shigemi'),('/programs/?q=zzzznotaprogramzzzz','No programs found'),('/archives/?program=nativesong&date=2026-10-12','Native Song'),('/events/?month=2026-11','Laurie Lewis')]:
    if needle not in lookup[p]: failures.append(f'{p}: expected filtered result missing')
if 'https://kzfr-donate.creek.org/' not in lookup['/donate/']: failures.append('Official donation destination absent')
if '<audio' not in lookup['/'] or 'preload="none"' not in lookup['/']: failures.append('Audio preload control absent')
if re.search(r'<audio[^>]+\ssrc=',lookup['/']): failures.append('Audio URL loads before activation')
# Follow every internal content link seen on representative listing pages.
internal=set()
for path in ['/','/programs/','/archives/','/events/','/underwriters/']:
    parser=Links();parser.feed(lookup[path])
    for href in parser.links:
        if href.startswith(base+'/') and '?' not in href and '#' not in href: internal.add(href[len(base):])
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
    linked=list(pool.map(get,sorted(internal-set(paths))))
for r in linked:
    if r['status']!=200: failures.append(f'Linked {r["path"]}: HTTP {r["status"]}')
report={'base_url':base,'checked_at_utc':time.strftime('%Y-%m-%dT%H:%M:%SZ',time.gmtime()),'checks':[{k:v for k,v in r.items() if k!='body'} for r in results],'internal_link_count':len(internal),'additional_link_checks':len(linked),'failures':failures,'passed':not failures,'timing_note':'Wall-clock urllib fetch from test host, unthrottled; not a mobile rendering benchmark.'}
print(json.dumps(report,indent=2))
raise SystemExit(bool(failures))
