export interface ExcelColumn {
    key: string;
    label: string;
}

export interface ExcelExportOptions {
    title?: string;
    filters?: Record<string, unknown> | Array<{ label: string; value: unknown }>;
    currencyColumns?: string[];
}

const xmlEscape = (value: unknown): string => String(value ?? '')
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');

const safeFilename = (filename: string): string => {
    const normalized = filename
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9_-]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .toLowerCase();

    return normalized || 'laporan';
};

const isCurrencyColumn = (column: ExcelColumn, explicitColumns: Set<string>): boolean => {
    if (explicitColumns.has(column.key)) return true;
    if (/poin|quantity|qty|jumlah|stock|stok|persen|percent|share|date|tanggal|time|waktu|_at\b|\bat\b/i.test(`${column.key} ${column.label}`)) return false;

    return /nominal|amount|harga|price|omset|omzet|revenue|pendapatan|income|paid|payment|discount|diskon|pajak|tax|saldo|cost|biaya|cash|tunai|piutang|refund|kembalian|profit|komisi|margin|subtotal/i.test(`${column.key} ${column.label}`);
};

const cellXml = (value: unknown, styleId?: string): string => {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return `<Cell${styleId ? ` ss:StyleID="${styleId}"` : ''}><Data ss:Type="Number">${value}</Data></Cell>`;
    }

    if (typeof value === 'boolean') {
        return `<Cell${styleId ? ` ss:StyleID="${styleId}"` : ''}><Data ss:Type="Boolean">${value ? 1 : 0}</Data></Cell>`;
    }

    const text = value && typeof value === 'object'
        ? JSON.stringify(value)
        : String(value ?? '');

    if (styleId && /^-?\d+(?:\.\d+)?$/.test(text)) {
        return `<Cell ss:StyleID="${styleId}"><Data ss:Type="Number">${Number(text)}</Data></Cell>`;
    }

    return `<Cell${styleId ? ` ss:StyleID="${styleId}"` : ''}><Data ss:Type="String">${xmlEscape(text)}</Data></Cell>`;
};

const filterEntries = (filters: ExcelExportOptions['filters']): Array<[string, string]> => {
    if (Array.isArray(filters)) {
        return filters
            .filter(({ value }) => value !== null && value !== undefined && String(value).trim() !== '')
            .map(({ label, value }) => [label, String(value)]);
    }

    if (!filters) return [];

    return Object.entries(filters)
        .filter(([key, value]) => !['page', 'per_page', 'theme'].includes(key) && value !== null && value !== undefined && String(value).trim() !== '')
        .map(([key, value]) => [key.replace(/[_-]+/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()), String(value)]);
};

export const downloadExcelTable = <Row extends object>(
    filename: string,
    sheetName: string,
    columns: ExcelColumn[],
    rows: Row[],
    options: ExcelExportOptions = {},
): void => {
    const safeSheetName = sheetName.replace(/[\\/:?*\[\]]/g, ' ').trim().slice(0, 31) || 'Laporan';
    const columnCount = Math.max(columns.length, 1);
    const title = options.title ?? sheetName;
    const filters = filterEntries(options.filters);
    const explicitCurrencyColumns = new Set(options.currencyColumns ?? []);
    const titleMerge = columnCount > 1 ? ` ss:MergeAcross="${columnCount - 1}"` : '';
    const valueMerge = columnCount > 2 ? ` ss:MergeAcross="${columnCount - 2}"` : '';
    const fullRowMerge = columnCount > 1 ? ` ss:MergeAcross="${columnCount - 1}"` : '';
    const titleRow = `<Row><Cell ss:StyleID="Title"${titleMerge}><Data ss:Type="String">${xmlEscape(title)}</Data></Cell></Row>`;
    const filterTitle = `<Row><Cell ss:StyleID="MetaHeader"${fullRowMerge}><Data ss:Type="String">Filter diterapkan</Data></Cell></Row>`;
    const filterRows = filters.length > 0
        ? filters.map(([label, value]) => `<Row><Cell ss:StyleID="MetaLabel"><Data ss:Type="String">${xmlEscape(label)}</Data></Cell><Cell ss:StyleID="MetaValue"${valueMerge}><Data ss:Type="String">${xmlEscape(value)}</Data></Cell></Row>`).join('')
        : `<Row><Cell ss:StyleID="MetaValue"${fullRowMerge}><Data ss:Type="String">Semua data</Data></Cell></Row>`;
    const header = `<Row>${columns.map((column) => `<Cell ss:StyleID="Header"><Data ss:Type="String">${xmlEscape(column.label)}</Data></Cell>`).join('')}</Row>`;
    const body = rows.map((row) => `<Row>${columns.map((column) => cellXml(
        (row as Record<string, unknown>)[column.key],
        isCurrencyColumn(column, explicitCurrencyColumns) ? 'Currency' : undefined,
    )).join('')}</Row>`).join('');
    const watermark = `<Row><Cell ss:StyleID="Watermark"${fullRowMerge}><Data ss:Type="String">veestore</Data></Cell></Row>`;
    const xml = `<?xml version="1.0" encoding="UTF-8"?><?mso-application progid="Excel.Sheet"?>` +
        `<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">` +
        `<Styles>` +
        `<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="15" ss:Color="#243047"/><Alignment ss:Vertical="Center"/></Style>` +
        `<Style ss:ID="MetaHeader"><Font ss:Bold="1" ss:Color="#586174"/><Interior ss:Color="#F5F7FA" ss:Pattern="Solid"/></Style>` +
        `<Style ss:ID="MetaLabel"><Font ss:Bold="1" ss:Color="#586174"/><Interior ss:Color="#F5F7FA" ss:Pattern="Solid"/></Style>` +
        `<Style ss:ID="MetaValue"><Font ss:Color="#586174"/><Interior ss:Color="#F5F7FA" ss:Pattern="Solid"/></Style>` +
        `<Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#E8ECF4" ss:Pattern="Solid"/></Style>` +
        `<Style ss:ID="Currency"><NumberFormat ss:Format="&quot;Rp&quot; #,##0;[Red]-&quot;Rp&quot; #,##0"/></Style>` +
        `<Style ss:ID="Watermark"><Font ss:Italic="1" ss:Size="10" ss:Color="#D9DEE8"/><Alignment ss:Horizontal="Center"/></Style>` +
        `</Styles>` +
        `<Worksheet ss:Name="${xmlEscape(safeSheetName)}"><Table>${titleRow}${filterTitle}${filterRows}${header}${body}${watermark}</Table></Worksheet></Workbook>`;
    const blob = new Blob([xml], { type: 'application/vnd.ms-excel;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = `${safeFilename(filename)}.xls`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
};
