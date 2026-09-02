import React from 'react';

const INLINE = /(\*\*[^*]+\*\*|\*[^*\n]+\*|\[[^\]]+\]\([^)]+\))/g;

function inline(text: string, prefix: string): React.ReactNode[] {
    const nodes: React.ReactNode[] = [];
    let cursor = 0;
    let token = 0;
    let match: RegExpExecArray | null;

    INLINE.lastIndex = 0;

    while ((match = INLINE.exec(text)) !== null) {
        if (match.index > cursor) nodes.push(text.slice(cursor, match.index));

        const raw = match[0];
        const key = `${prefix}-i${token++}`;

        if (raw.startsWith('**')) {
            nodes.push(<strong key={key} className="font-bold text-gray-900">{raw.slice(2, -2)}</strong>);
        } else if (raw.startsWith('[')) {
            nodes.push(
                <span key={key} className="text-[#1EB85A] underline">
                    {raw.slice(1, raw.indexOf(']'))}
                </span>,
            );
        } else {
            nodes.push(<em key={key}>{raw.slice(1, -1)}</em>);
        }

        cursor = INLINE.lastIndex;
    }

    if (cursor < text.length) nodes.push(text.slice(cursor));

    return nodes;
}

const HEADING_CLASS: Record<number, string> = {
    1: 'text-lg font-bold text-gray-900 mt-5 mb-2',
    2: 'text-base font-bold text-gray-900 mt-5 mb-2',
    3: 'text-sm font-bold text-gray-900 mt-4 mb-1.5',
};

export default function MarkdownPreview({ source }: { source: string }) {
    const blocks: React.ReactNode[] = [];
    let list: string[] = [];
    let ordered = false;
    let para: string[] = [];

    const flushList = () => {
        if (list.length === 0) return;
        const items = list.map((item, i) => (
            <li key={`li-${blocks.length}-${i}`}>{inline(item, `li-${blocks.length}-${i}`)}</li>
        ));
        blocks.push(
            ordered ? (
                <ol key={`ol-${blocks.length}`} className="list-decimal pl-5 my-2 space-y-1">{items}</ol>
            ) : (
                <ul key={`ul-${blocks.length}`} className="list-disc pl-5 my-2 space-y-1">{items}</ul>
            ),
        );
        list = [];
    };

    const flushPara = () => {
        if (para.length === 0) return;
        const text = para.join(' ');
        blocks.push(
            <p key={`p-${blocks.length}`} className="my-2 leading-relaxed">
                {inline(text, `p-${blocks.length}`)}
            </p>,
        );
        para = [];
    };

    source.split('\n').forEach((line) => {
        const trimmed = line.trim();

        if (trimmed === '') {
            flushList();
            flushPara();
            return;
        }

        const heading = /^(#{1,3})\s+(.*)$/.exec(trimmed);
        if (heading) {
            flushList();
            flushPara();
            const level = (heading[1] ?? '#').length;
            blocks.push(
                <p key={`h-${blocks.length}`} className={HEADING_CLASS[level] ?? HEADING_CLASS[3]}>
                    {inline(heading[2] ?? '', `h-${blocks.length}`)}
                </p>,
            );
            return;
        }

        if (/^(-{3,}|\*{3,})$/.test(trimmed)) {
            flushList();
            flushPara();
            blocks.push(<hr key={`hr-${blocks.length}`} className="my-4 border-gray-200" />);
            return;
        }

        const bullet = /^[-*]\s+(.*)$/.exec(trimmed);
        const numbered = /^\d+[.)]\s+(.*)$/.exec(trimmed);

        if (bullet || numbered) {
            flushPara();
            const nextOrdered = numbered !== null;
            if (list.length > 0 && nextOrdered !== ordered) flushList();
            ordered = nextOrdered;
            list.push((bullet ?? numbered)?.[1] ?? '');
            return;
        }

        flushList();
        para.push(trimmed);
    });

    flushList();
    flushPara();

    return <div className="text-sm text-gray-600">{blocks}</div>;
}
