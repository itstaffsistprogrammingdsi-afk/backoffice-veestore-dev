const escapeHtml = (value: string): string =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

const placeholderHref = (value: string): boolean =>
    /(contoh\/|example\.com|localhost|127\.0\.0\.1|placeholder|your-domain|xxx|\.\.\.)/i.test(value);

const safeHref = (value: string): string | null => {
    const url = value.trim();

    if (!url || placeholderHref(url) || /^(javascript|data|vbscript|mailto|tel):/i.test(url)) {
        return null;
    }

    if (/^\/(?!\/)/.test(url)) {
        return url;
    }

    if (/^https?:\/\//i.test(url)) {
        if (typeof window === 'undefined') {
            return null;
        }

        try {
            return new URL(url).origin === window.location.origin ? url : null;
        } catch {
            return null;
        }
    }

    return null;
};

const isExternal = (url: string): boolean => {
    if (!/^https?:\/\//i.test(url)) {
        return false;
    }

    if (typeof window === 'undefined') {
        return true;
    }

    try {
        return new URL(url).origin !== window.location.origin;
    } catch {
        return true;
    }
};

const inline = (value: string): string => {
    const html = escapeHtml(value)
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        .replace(/`([^`]+)`/g, '<code>$1</code>');

    return html.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (match, text: string, href: string) => {
        const url = safeHref(href);

        if (!url) {
            return match;
        }

        const external = isExternal(url) ? ' target="_blank" rel="noopener noreferrer"' : '';

        return `<a href="${url}"${external}>${text}</a>`;
    });
};

export function renderAssistantMarkdown(value: string): string {
    const lines = value.replace(/\r\n/g, '\n').split('\n');
    const output: string[] = [];
    let listType: 'ul' | 'ol' | null = null;
    let inCode = false;
    let codeBuffer: string[] = [];

    const closeList = () => {
        if (listType) {
            output.push(`</${listType}>`);
            listType = null;
        }
    };

    for (const rawLine of lines) {
        const line = rawLine.trimEnd();

        if (line.trim().startsWith('```')) {
            if (inCode) {
                output.push(`<pre><code>${escapeHtml(codeBuffer.join('\n'))}</code></pre>`);
                codeBuffer = [];
                inCode = false;
            } else {
                closeList();
                inCode = true;
            }
            continue;
        }

        if (inCode) {
            codeBuffer.push(rawLine);
            continue;
        }

        if (line.trim() === '') {
            closeList();
            continue;
        }

        const heading = line.match(/^(#{1,3})\s+(.+)$/);
        if (heading) {
            closeList();
            const level = Math.min(heading[1].length + 2, 5);
            output.push(`<h${level}>${inline(heading[2])}</h${level}>`);
            continue;
        }

        const unordered = line.match(/^\s{0,3}[-*]\s+(.+)$/);
        if (unordered) {
            if (listType !== 'ul') {
                closeList();
                output.push('<ul>');
                listType = 'ul';
            }
            output.push(`<li>${inline(unordered[1])}</li>`);
            continue;
        }

        const ordered = line.match(/^\s{0,3}\d+\.\s+(.+)$/);
        if (ordered) {
            if (listType !== 'ol') {
                closeList();
                output.push('<ol>');
                listType = 'ol';
            }
            output.push(`<li>${inline(ordered[1])}</li>`);
            continue;
        }

        closeList();
        output.push(`<p>${inline(line)}</p>`);
    }

    closeList();

    if (inCode && codeBuffer.length > 0) {
        output.push(`<pre><code>${escapeHtml(codeBuffer.join('\n'))}</code></pre>`);
    }

    return output.join('');
}
