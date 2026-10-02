import io

lines = io.open('resources/views/partials/header.blade.php', encoding='utf-8').read().split('\n')

for number in range(175, 202):
    if number <= len(lines):
        print('[{}] {}'.format(number, lines[number - 1]))