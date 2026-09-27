"""Build an allowlisted runtime ZIP. Never includes private config or user data.
Usage: python3 scripts/package.py /private/output/runtime.zip [--portfolio-update]
"""
import argparse
from pathlib import Path
import zipfile

root = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('output', type=Path)
parser.add_argument('--portfolio-update', action='store_true')
args = parser.parse_args()
files = ['index.php', 'app/core.php', 'app/views.php', 'scopri.php', 'assets/scopri.css', 'assets/scopri.js']
if not args.portfolio_update:
    files += ['image.php', '.htaccess', 'app/actions.php', 'app/schema.php', 'app/setup.php', 'app/.htaccess',
              'assets/app.css', 'assets/app.js', 'assets/favicon.svg', 'config/.htaccess',
              'config/local.example.php', 'config/install.example.php', 'config/https-proxy.example.php', 'storage/.htaccess']
for name in files:
    if not (root / name).is_file() or (root / name).is_symlink():
        raise SystemExit('Missing or symbolic source: ' + name)
args.output.parent.mkdir(parents=True, exist_ok=True)
with zipfile.ZipFile(args.output, 'x', zipfile.ZIP_DEFLATED) as archive:
    directories = {str(Path(name).parent) + '/' for name in files if Path(name).parent != Path('.')}
    if not args.portfolio_update:
        directories.update(['storage/uploads/', 'storage/logs/'])
    for directory in sorted(directories):
        archive.writestr(directory, b'')
    for name in sorted(files):
        archive.write(root / name, name)
print(f'{len(files)} files packaged in {args.output}')
