@push('head')
<style>
    /* Kartu survei mitra */
    .mt-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
    .mt-card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 2px 8px var(--shadow);padding:16px 18px;display:grid;gap:12px;align-content:start}
    .mt-card h3{margin:0;font-size:14px;font-weight:700;color:var(--brand-dark);line-height:1.35;text-wrap:balance}
    .mt-card .meta{display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center;font-size:12px;color:var(--muted)}
    .mt-card .late{color:var(--st-late-ink);font-weight:600}
    .mt-big{display:flex;align-items:baseline;justify-content:space-between;gap:10px}
    .mt-big b{font-size:21px;font-weight:800;letter-spacing:-.02em;font-variant-numeric:tabular-nums;color:var(--text)}
    .mt-big span{font-size:12px;color:var(--muted)}
    .mt-track{display:flex;height:8px;border-radius:4px;overflow:hidden;background:var(--bar-bg, var(--soft))}
    .mt-track i{display:block;height:100%}
    .mt-counts{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}
    .mt-counts div{border:1px solid var(--line);border-radius:10px;padding:7px 8px;background:var(--soft);min-width:0}
    .mt-counts small{display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:var(--muted);white-space:nowrap}
    .mt-counts b{display:block;font-size:15px;font-variant-numeric:tabular-nums;margin-top:2px}
    .mt-sw{width:8px;height:8px;border-radius:2px;flex:none}
    .mt-card .foot{display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap;padding-top:4px}
    .mt-card .foot p{margin:0;font-size:12px;color:var(--muted)}

    /* Daftar entri mitra */
    .mt-entries{display:grid;gap:8px}
    .mt-entry{display:grid;grid-template-columns:48px minmax(0,1fr) auto;gap:12px;align-items:center;border:1px solid var(--line);border-radius:12px;padding:10px 12px;background:var(--card)}
    .mt-entry img,.mt-entry .ph{width:48px;height:48px;border-radius:9px;object-fit:cover;border:1px solid var(--line);background:var(--soft)}
    .mt-entry .ph{display:grid;place-items:center;color:var(--muted)}
    .mt-entry .ph svg{width:20px;height:20px}
    .mt-entry b{display:block;font-size:13.5px}
    .mt-entry small{display:block;font-size:12px;color:var(--muted);margin-top:2px}
    .mt-entry .side{display:flex;gap:8px;align-items:center}
    @media (max-width:560px){
        .mt-entry{grid-template-columns:44px minmax(0,1fr)}
        .mt-entry .side{grid-column:1/-1;justify-content:space-between}
    }
</style>
@endpush
