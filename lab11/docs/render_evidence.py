#!/usr/bin/env python3
"""PNG-представления фактических журналов и графики из benchmark.json."""
from pathlib import Path
from statistics import median
import textwrap
import json
import os
from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
EVIDENCE = ROOT / 'docs' / 'evidence'
IMAGES = ROOT / 'images'
IMAGES.mkdir(exist_ok=True)

benchmark_file = EVIDENCE / 'benchmark.json'
if not benchmark_file.exists():
    parsed = []
    for line in (EVIDENCE / 'benchmark.txt').read_text().splitlines()[1:]:
        mode, run, report_id, http_ms, wait_ms, total_ms = line.split(';')
        parsed.append({'mode': mode, 'run': int(run), 'report_id': int(report_id),
            'http_ms': float(http_ms), 'wait_ms': float(wait_ms), 'total_ms': float(total_ms)})
    benchmark_file.write_text(json.dumps(parsed, ensure_ascii=False, indent=2) + '\n')
rows = json.loads(benchmark_file.read_text())
async_rows = [x for x in rows if x['mode'] == 'async']
sync_rows = [x for x in rows if x['mode'] == 'sync']
summary = (
    '200 006 заявок, CSV-отчёт, пять прогонов в каждом режиме\n'
    f"async: HTTP медиана {median(x['http_ms'] for x in async_rows):.2f} мс; "
    f"ожидание {median(x['wait_ms'] for x in async_rows):.2f} мс; "
    f"всё {median(x['total_ms'] for x in async_rows):.2f} мс\n"
    f"sync: HTTP медиана {median(x['http_ms'] for x in sync_rows):.2f} мс; "
    f"всё {median(x['total_ms'] for x in sync_rows):.2f} мс\n"
    'Данные: docs/evidence/benchmark.json; отдельный воркер работал во время async-прогонов.\n'
)
(EVIDENCE / 'comparison.txt').write_text(summary)
(EVIDENCE / 'retry-combined.txt').write_text(
    'Вывод воркера:\n' + (EVIDENCE / 'retry-worker.txt').read_text()
    + '\nСостояние Redis:\n' + (EVIDENCE / 'retry-dead.txt').read_text()
)

sources = [
    ('01-queue-check.png', 'Проверка очереди', 'queue-check.txt'),
    ('02-enqueue.png', 'Постановка задачи', 'enqueue.txt'),
    ('03-worker.png', 'Работа воркера', 'worker-demo.txt'),
    ('04-sync-vs-async.png', 'Синхронный и асинхронный режимы', 'comparison.txt'),
    ('05-retry-dead.png', 'Повторы и мёртвая очередь', 'retry-combined.txt'),
    ('06-crash-recovery.png', 'Восстановление после SIGKILL', 'crash-recovery.txt'),
    ('07-report.png', 'Формирование CSV-отчёта', 'report.txt'),
    ('08-benchmark.png', 'Фактические измерения', 'benchmark.txt'),
    ('09-tests.png', 'Автоматические проверки', 'tests.txt'),
]

for filename, title, source in sources:
    body = (EVIDENCE / source).read_text().replace('\r', '')
    lines = []
    for raw in body.splitlines():
        lines.extend(textwrap.wrap(raw, width=105, break_long_words=True,
            replace_whitespace=False) or [''])
    heading_font = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 30)
    note_font = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf', 17)
    log_font = ImageFont.truetype('/usr/share/fonts/truetype/dejavu/DejaVuSansMono.ttf', 17)
    height = max(620, 210 + 26 * len(lines))
    canvas = Image.new('RGB', (1450, height), '#f5f7fa')
    draw = ImageDraw.Draw(canvas)
    draw.rounded_rectangle((32, 28, 1418, height - 28), radius=13, fill='white', outline='#dbe2eb', width=2)
    draw.text((66, 58), title, font=heading_font, fill='#142236')
    draw.text((66, 108), f'Фактический вывод · docs/evidence/{source}', font=note_font, fill='#526273')
    for number, line in enumerate(lines):
        draw.text((66, 160 + number * 26), line, font=log_font, fill='#142236')
    canvas.save(IMAGES / filename)

os.environ['MPLCONFIGDIR'] = '/tmp/lab11-matplotlib'
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt

plt.rcParams['font.family'] = 'DejaVu Sans'
fig, ax = plt.subplots(figsize=(11, 5.5))
positions = range(1, 6)
ax.plot(positions, [x['http_ms'] for x in async_rows], 'o-', linewidth=2.5, label='Очередь: ответ HTTP')
ax.plot(positions, [x['http_ms'] for x in sync_rows], 's-', linewidth=2.5, label='Без очереди: ответ HTTP')
ax.set_title('Время HTTP-ответа при построении CSV из 200 006 заявок')
ax.set_xlabel('Номер прогона')
ax.set_ylabel('Время, мс')
ax.set_xticks(list(positions))
ax.grid(alpha=.25)
ax.legend()
fig.tight_layout()
fig.savefig(IMAGES / '10-chart-latency.png', dpi=170)
plt.close(fig)

fig, ax = plt.subplots(figsize=(12, 4.2))
ax.set_xlim(0, 12)
ax.set_ylim(0, 4)
ax.axis('off')
boxes = [(.4, 'HTTP\nPATCH / POST'), (3.0, 'Redis\nqueue:default'),
         (5.6, 'BLMOVE\nprocessing'), (8.2, 'Worker\nJobs::handle'),
         (10.6, 'ACK\nLREM')]
for x, label in boxes:
    ax.text(x + .9, 2.65, label, ha='center', va='center', fontsize=12,
        bbox={'boxstyle': 'round,pad=.65', 'fc': '#e7f0ff', 'ec': '#3572bc'})
for x in [2.25, 4.85, 7.45, 9.95]:
    ax.annotate('', xy=(x + .7, 2.65), xytext=(x, 2.65),
        arrowprops={'arrowstyle': '->', 'lw': 2, 'color': '#3572bc'})
ax.text(6.5, .9, 'Ошибка → delayed (2, 4 с) → queue:default · после 3 неудач → dead',
    ha='center', fontsize=12, color='#914420')
ax.text(6.5, .35, 'SIGKILL → задача остаётся в processing → recover при запуске воркера',
    ha='center', fontsize=12, color='#526273')
fig.tight_layout()
fig.savefig(IMAGES / '11-queue-flow.png', dpi=170)
plt.close(fig)
