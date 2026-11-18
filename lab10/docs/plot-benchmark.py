"""Build charts and pooled summaries from two actual benchmark JSON files."""
from pathlib import Path
import json,math
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
r=Path(__file__).resolve().parents[1]
a=json.loads((r/'docs/benchmark-1.json').read_text())
b=json.loads((r/'docs/benchmark-2.json').read_text())
rows=[]
for first,second in zip(a['scenarios'],b['scenarios']):
    assert first['path']==second['path']
    row={'name':first['name'],'path':first['path']}
    for mode in ['off','warm']:
        samples=sorted(first[mode]['samples_ms']+second[mode]['samples_ms'])
        loads=[first[mode+'_load'],second[mode+'_load']]
        row[mode]={'mean_ms':sum(samples)/len(samples),'p95_ms':samples[math.ceil(.95*len(samples))-1],
                   'rps':sum(x['requests'] for x in loads)/sum(x['seconds'] for x in loads)}
    row['speedup']=row['off']['mean_ms']/row['warm']['mean_ms'];rows.append(row)
(r/'docs/benchmark-summary.json').write_text(json.dumps(rows,ensure_ascii=False,indent=2)+'\n')
names=[x['name'] for x in rows]
plt.rcParams.update({'font.family':'DejaVu Sans','font.size':11})
for field,title,label,out in [
 ('mean_ms','Средняя задержка: 60 запросов на режим','Время ответа, мс','09-chart-latency.png'),
 ('rps','Пропускная способность: 80 запросов, concurrency=4','Запросов в секунду','10-chart-rps.png')]:
    fig,ax=plt.subplots(figsize=(12,7),layout='constrained')
    y=list(range(len(rows)))
    off=[row['off'][field] for row in rows];warm=[row['warm'][field] for row in rows]
    bars1=ax.barh([x-.19 for x in y],off,height=.36,color='#3182ce',label='Без кеша (BYPASS)')
    bars2=ax.barh([x+.19 for x in y],warm,height=.36,color='#38a169',label='Прогретый кеш (HIT)')
    ax.set_yticks(y,names);ax.invert_yaxis();ax.set_xlabel(label);ax.set_title(title+'\n500 000 заявок; два прогона')
    ax.bar_label(bars1,fmt='%.1f',padding=4,fontsize=9);ax.bar_label(bars2,fmt='%.1f',padding=4,fontsize=9)
    ax.set_xlim(0,max(off+warm)*1.18);ax.grid(axis='x',alpha=.18);ax.set_axisbelow(True);ax.legend(loc='lower right')
    fig.savefig(r/'images'/out,dpi=160);plt.close(fig)
