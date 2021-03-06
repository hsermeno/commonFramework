#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# Small script to merge an existing translations file and a new translations
# file generated by check-translations.py (and filled by a translator).
# It will use the translations file of a base language to copy its structure
# for clarity.

BASE_LANGUAGE = 'fr'
TARGET_LANGUAGE = 'de'
FILENAME = 'algorea.json'
TARGET_FILENAME = 'algorea.json.new'
NEW_TRANSLATIONS_FILE = 'translations_german.json'

import json, re

# Read target language translations
try:
    available_translations = json.load(open('%s/%s' % (TARGET_LANGUAGE, FILENAME)))
except:
    print("Warning: Couldn't read `%s/%s`." % (TARGET_LANGUAGE, FILENAME))
    available_translations = {}

print("Loaded %d available_translations." % len(available_translations.keys()))

# Read new translations
new_translations = json.load(open(NEW_TRANSLATIONS_FILE, 'r'))
ntnb = 0
for item in new_translations[TARGET_LANGUAGE]:
    if len(item) != 3:
        print("Warning: item with wrong length: %s" % item)
        continue

    available_translations[item[0]] = item[2]
    ntnb += 1
print("Loaded %d new_translations, %d total translations." % (ntnb, len(available_translations.keys())))

# Read original translations file and write new one
tr_regexp = re.compile(r'^\s*"([^"]+)"\s*:\s*')
target_file = open('%s/%s' % (TARGET_LANGUAGE, TARGET_FILENAME), 'w')
write_buffer = None # Buffer to check if we should put a comma or not
for l in open('%s/%s' % (BASE_LANGUAGE, FILENAME), 'r'):
    tr_m = tr_regexp.match(l)
    if tr_m:
        # It's a translation line
        line_base = tr_m.group(0)
        key = tr_m.group(1)
        if key in available_translations:
            if write_buffer is not None:
                # Complicated things just to know if we need a comma...
                target_file.write(',\n')
                target_file.write(write_buffer)
                write_buffer = ''

            new_line = '%s"%s"' % (line_base, available_translations[key].replace('"', '\\"'))
            target_file.write(new_line)
            write_buffer = ''
        else:
            print("Warning: key `%s` not found in available_translations." % key)
    else:
        # It's another line
        l_strip = l.strip()
        if l_strip == '{':
            target_file.write(l)
        elif l_strip == '}':
            target_file.write('\n')
            target_file.write(write_buffer)
            write_buffer = ''
            target_file.write(l)
        else:
            if l_strip != '':
                print("Warning: line `%s` unrecognized; copying as-is." % l[:-1])
            if write_buffer is None: write_buffer = ''
            write_buffer += l

target_file.write(write_buffer)
target_file.close()
print("Done!")
