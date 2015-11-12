@echo off
del ..\mini-config-docs\* /Q
rmdir ..\mini-config-docs\resources /S /Q
php apigen.phar generate -s src -d ..\mini-config-docs --title="mini-config" --tree --base-url "http://abreksa4.github.io/mini-config-docs/"
