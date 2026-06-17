#!/usr/bin/env python3
"""
Generate a consolidated Lucide icon manifest at frontend/src/assets/lucide-icons.json.
Downloads lucide-static tarball from npm registry, extracts SVG inner content and tags.
"""
import json, os, time, sys, tarfile, io, re
from urllib.request import urlopen

ASSETS_DIR = os.path.join(os.path.dirname(__file__), '..', 'frontend', 'src', 'assets')
OUTPUT = os.path.join(ASSETS_DIR, 'lucide-icons.json')
VERSION = '1.20.0'
TAGS_URL = f'https://cdn.jsdelivr.net/npm/lucide-static@{VERSION}/tags.json'
TARBALL_URL = f'https://registry.npmjs.org/lucide-static/-/lucide-static-{VERSION}.tgz'

SVG_INNER_RE = re.compile(r'<svg[^>]*>(.*?)</svg>', re.DOTALL)

def extract_svg_inner(svg_content: str) -> str:
    m = SVG_INNER_RE.search(svg_content)
    return m.group(1).strip() if m else ''

def main():
    os.makedirs(ASSETS_DIR, exist_ok=True)

    print(f'Fetching tags from {TAGS_URL} ...')
    with urlopen(TAGS_URL, timeout=30) as resp:
        tags_data: dict[str, list[str]] = json.loads(resp.read().decode())

    print(f'Downloading lucide-static@{VERSION} tarball (~3 MB) ...')
    with urlopen(TARBALL_URL, timeout=120) as resp:
        tarball_bytes = resp.read()
    print(f'  Downloaded {len(tarball_bytes)/1024:.0f} KB')

    print('Extracting SVGs from tarball ...')
    svgs: dict[str, str] = {}
    with tarfile.open(fileobj=io.BytesIO(tarball_bytes), mode='r:gz') as tar:
        for member in tar.getmembers():
            # icons are at package/icons/<name>.svg
            if member.name.startswith('package/icons/') and member.name.endswith('.svg'):
                icon_name = member.name.rsplit('/', 1)[-1][:-4]
                f = tar.extractfile(member)
                if f:
                    svgs[icon_name] = extract_svg_inner(f.read().decode('utf-8'))
    print(f'  Extracted {len(svgs)} SVGs')

    manifest: dict[str, dict] = {}
    for name, tags_list in tags_data.items():
        manifest[name] = {'tags': tags_list, 'svg': svgs.get(name, '')}

    # include any icons that have SVG but no tags entry
    for name, svg in svgs.items():
        if name not in manifest:
            manifest[name] = {'tags': [], 'svg': svg}

    output = {
        '_meta': {
            'source': f'lucide-static@{VERSION}',
            'generated': time.strftime('%Y-%m-%dT%H:%M:%SZ', time.gmtime()),
            'count': len(manifest),
        },
        'icons': manifest,
    }

    with open(OUTPUT, 'w', encoding='utf-8') as f:
        json.dump(output, f, ensure_ascii=False, separators=(',', ':'))

    size = os.path.getsize(OUTPUT)
    print(f'Done! {len(manifest)} icons → {OUTPUT} ({size/1024:.0f} KB)')

if __name__ == '__main__':
    main()
