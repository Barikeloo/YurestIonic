#!/usr/bin/env python3
"""
ESC/POS TCP simulator — listens on port 9100 and renders received tickets as text.
Run on the Mac host; configure printer IP as "host.docker.internal" in the admin panel.
"""
import socket
import re
import sys

PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 9100

# Order matters: longer / more-specific patterns FIRST so the alternation
# never greedily matches a shorter prefix and leaves param bytes behind.
ESCPOS_STRIP = re.compile(
    rb'\x1b\x40'           # ESC @ — initialize (2 bytes, no param)
    rb'|\x1b\x61.'         # ESC a n — align (3 bytes)
    rb'|\x1b\x45.'         # ESC E n — bold on/off (3 bytes)
    rb'|\x1b\x64.'         # ESC d n — feed n lines (3 bytes)
    rb'|\x1b\x21.'         # ESC ! n — print mode (3 bytes)
    rb'|\x1b\x33.'         # ESC 3 n — line spacing (3 bytes)
    rb'|\x1b\x4d.'         # ESC M n — font select (3 bytes)
    rb'|\x1d\x21.'         # GS ! n — character size (3 bytes)
    rb'|\x1d\x56.'         # GS V n — cut (3 bytes)
    rb'|\x1d\x42.'         # GS B n — white/black reverse (3 bytes)
    rb'|\x1d\x57..'        # GS W nL nH — print width (4 bytes)
    rb'|\x1d[ABHVW!#]..'   # GS multi-byte commands (4 bytes, catch-all)
    rb'|\x1b[@-Z\\\-_`-z]' # ESC + any single ctrl byte (2 bytes, catch-all)
    rb'|\r'
)

def render(data: bytes) -> str:
    text = ESCPOS_STRIP.sub(b'', data)
    # Replace non-printable control chars except LF
    cleaned = bytes(b if b == 0x0a or 0x20 <= b <= 0x7e else 0x3f for b in text)
    return cleaned.decode('ascii', errors='replace')

def main():
    srv = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    srv.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    srv.bind(('0.0.0.0', PORT))
    srv.listen(5)
    print(f'ESC/POS simulator listening on :{PORT}')
    print(f'Configure printer IP as "host.docker.internal" in the admin panel.\n', flush=True)

    while True:
        conn, addr = srv.accept()
        print(f'\n{"="*48}')
        print(f'Connection from {addr[0]}:{addr[1]}')
        print('='*48)
        chunks = []
        try:
            conn.settimeout(1.0)
            while True:
                chunk = conn.recv(4096)
                if not chunk:
                    break
                chunks.append(chunk)
        except socket.timeout:
            pass
        finally:
            conn.close()

        if chunks:
            data = b''.join(chunks)
            print(render(data), flush=True)
            print('='*48, flush=True)

if __name__ == '__main__':
    main()
