<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

    .page { padding: 24px 28px; }

    /* ── Cabeçalho ── */
    .header { border-bottom: 2px solid #f59e0b; padding-bottom: 12px; margin-bottom: 16px; }
    .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .school-name { font-size: 15px; font-weight: bold; color: #1e293b; }
    .school-sub  { font-size: 9px; color: #64748b; margin-top: 2px; }
    .doc-title   { font-size: 13px; font-weight: bold; color: #f59e0b; text-align: right; }
    .doc-sub     { font-size: 9px; color: #64748b; text-align: right; margin-top: 2px; }

    /* ── Bloco de dados do aluno ── */
    .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; }
    .info-box-title { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; font-weight: 700; margin-bottom: 8px; }
    .info-grid { display: flex; gap: 20px; flex-wrap: wrap; }
    .info-item label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; display: block; }
    .info-item span  { font-size: 10px; font-weight: 600; color: #1e293b; }
    .info-item-wide  { flex: 1 0 100%; }

    /* ── Bloco de operação ── */
    .op-box { border: 1px solid #fde68a; background: #fffbeb; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; }
    .op-box-title { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.06em; color: #b45309; font-weight: 700; margin-bottom: 8px; }

    /* ── Bloco de aviso/declaração ── */
    .decl-box { border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; font-size: 9px; color: #1e40af; line-height: 1.5; }
    .danger-box { border: 1px solid #fecaca; background: #fef2f2; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; }
    .danger-box-title { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.06em; color: #dc2626; font-weight: 700; margin-bottom: 6px; }

    /* ── Tabela de notas ── */
    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    thead th { background: #1e293b; color: #fff; font-size: 8px; text-transform: uppercase; letter-spacing: 0.05em; padding: 6px 8px; text-align: center; }
    thead th:first-child { text-align: left; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 5px 8px; font-size: 9px; border-bottom: 1px solid #e2e8f0; text-align: center; }
    tbody td:first-child { text-align: left; font-weight: 600; }

    /* ── Lista de documentos ── */
    .doc-list { margin-bottom: 14px; }
    .doc-list-title { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; font-weight: 700; margin-bottom: 6px; }
    .doc-item { display: flex; justify-content: space-between; padding: 4px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9px; }
    .doc-item:last-child { border-bottom: none; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7.5px; font-weight: 700; }
    .b-entregue   { background: #dcfce7; color: #166534; }
    .b-pendente   { background: #fef9c3; color: #854d0e; }
    .b-dispensado { background: #f1f5f9; color: #475569; }

    /* ── Assinaturas ── */
    .signatures { display: flex; gap: 40px; justify-content: center; margin-top: 30px; margin-bottom: 16px; }
    .sig-line .line  { border-top: 1px solid #334155; width: 140px; margin: 0 auto 4px; }
    .sig-line .label { font-size: 8px; color: #64748b; text-align: center; }

    /* ── Rodapé ── */
    .footer { border-top: 1px solid #e2e8f0; padding-top: 8px; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; margin-top: 20px; }

    /* ── Selo de autenticidade ── */
    .seal { text-align: center; margin: 10px 0; font-size: 8px; color: #94a3b8; }
    .seal strong { font-size: 9px; color: #64748b; }

    /* ── Número de matrícula destacado ── */
    .reg-number { font-family: DejaVu Sans Mono, monospace; font-size: 16px; font-weight: 800; color: #f59e0b; }
</style>
