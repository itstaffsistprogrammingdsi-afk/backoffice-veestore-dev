import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { NodeTypes, createSimpleExpression } from '@vue/compiler-dom';
import { parse as parseSfc } from '@vue/compiler-sfc';

const translatedPropertyNames = new Set([
    'action', 'ariaLabel', 'description', 'emptyText', 'error', 'errorMessage', 'label', 'login', 'message', 'noResultsText', 'password', 'placeholder', 'searchPlaceholder', 'status', 'status_label', 'success', 'successMessage', 'title',
]);

const localizedTemplateNodes = (node: any, context: any) => {
    if (node.type === NodeTypes.ELEMENT) {
        const textAttributes = new Set(['alt', 'aria-description', 'aria-label', 'placeholder', 'title']);

        node.props = node.props.map((property: any) => {
            if (property.type === NodeTypes.ATTRIBUTE && textAttributes.has(property.name) && property.value) {
                return {
                    type: NodeTypes.DIRECTIVE,
                    name: 'bind',
                    exp: createSimpleExpression(`$t(${JSON.stringify(property.value.content)})`, false, property.loc),
                    arg: createSimpleExpression(property.name, true, property.loc),
                    modifiers: [],
                    loc: property.loc,
                };
            }

            if (
                property.type === NodeTypes.DIRECTIVE
                && property.name === 'bind'
                && property.arg?.isStatic
                && textAttributes.has(property.arg.content)
                && property.exp
                && property.exp.type === NodeTypes.SIMPLE_EXPRESSION
            ) {
                const expression = property.exp.content;
                const quotedText = expression.replace(/(['"])((?:\\.|(?!\1).)*)\1/g, (_match: string, _quote: string, value: string) => (
                    `$t(${JSON.stringify(value.replace(/\\(['"\\])/g, '$1'))})`
                ));

                if (quotedText !== expression) {
                    property.exp.content = quotedText;
                    property.exp.isStatic = false;
                } else if (/^(?:[\w$]+)(?:(?:\?\.|\.)[\w$]+)*$/.test(expression)) {
                    const lastName = expression.split(/\?\.|\./).at(-1);

                    if (lastName && translatedPropertyNames.has(lastName)) {
                        property.exp.content = `$t(${expression})`;
                        property.exp.isStatic = false;
                    }
                }
            }

            return property;
        });
    }

    if (node.type === NodeTypes.TEXT && node.content.trim() && !['pre', 'code', 'kbd', 'samp', 'textarea'].includes(context.parent?.tag)) {
        const content = node.content;
        const trimmed = content.trim();
        const leading = content.slice(0, content.indexOf(trimmed));
        const trailing = content.slice(content.indexOf(trimmed) + trimmed.length);
        const expression = `${leading ? `${JSON.stringify(leading)} + ` : ''}$t(${JSON.stringify(trimmed)})${trailing ? ` + ${JSON.stringify(trailing)}` : ''}`;

        context.replaceNode({
            type: NodeTypes.INTERPOLATION,
            content: createSimpleExpression(expression, false, node.loc),
            loc: node.loc,
        });
    }

    if (node.type === NodeTypes.INTERPOLATION && node.content.type === NodeTypes.SIMPLE_EXPRESSION) {
        const expression = node.content.content.trim();

        if (expression.includes('id-ID')) {
            context.replaceNode({
                type: NodeTypes.INTERPOLATION,
                content: createSimpleExpression(expression.replace(/(['"])id-ID\1/g, '$localeTag()'), false, node.content.loc),
                loc: node.loc,
            });
            return;
        }

        if (/^(?:[\w$]+)(?:(?:\?\.|\.)[\w$]+)*$/.test(expression)) {
            const lastName = expression.split(/\?\.|\./).at(-1);

            if (lastName && translatedPropertyNames.has(lastName)) {
                context.replaceNode({
                    type: NodeTypes.INTERPOLATION,
                    content: createSimpleExpression(`$t(${expression})`, false, node.content.loc),
                    loc: node.loc,
                });
            }
        }
    }
};

const localizedVueScripts = {
    name: 'veestore-vue-localization',
    enforce: 'pre' as const,
    transform(source: string, id: string) {
        if (!id.endsWith('.vue')) return null;

        const { descriptor } = parseSfc(source, { filename: id });
        const blocks = [descriptor.script, descriptor.scriptSetup].filter(Boolean) as NonNullable<typeof descriptor.script>[];
        const edits = blocks.map((block) => {
            let content = block.content;
            let needsImport = false;

            content = content.replace(
                /\b(action|ariaLabel|description|emptyText|error|errorMessage|label|login|message|noResultsText|password|placeholder|searchPlaceholder|status|status_label|success|successMessage|title)\s*:\s*(['"])((?:\\.|(?!\2).)*)\2(?=\s*[,}])/g,
                (_match, key: string, _quote: string, rawValue: string) => {
                    needsImport = true;
                    const value = rawValue.replace(/\\(['"\\])/g, '$1');

                    return `${key}: __veestoreTranslate(${JSON.stringify(value)})`;
                },
            );
            content = content.replace(/(['"])id-ID\1/g, () => {
                needsImport = true;
                return '__veestoreLocaleTag()';
            });

            if (needsImport) {
                content = `\nimport { translate as __veestoreTranslate, intlLocale as __veestoreLocaleTag } from '@/composables/i18n';\n${content}`;
            }

            return { start: block.loc.start.offset, end: block.loc.end.offset, content };
        });

        if (!edits.some((edit, index) => edit.content !== blocks[index]?.content)) return null;

        let transformed = source;
        for (const edit of edits.sort((left, right) => right.start - left.start)) {
            transformed = transformed.slice(0, edit.start) + edit.content + transformed.slice(edit.end);
        }

        return { code: transformed, map: null };
    },
};

export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    plugins: [
        localizedVueScripts,
        laravel({
            input: ['resources/js/app.ts'],
            refresh: true,
        }),
        vue({
            template: {
                compilerOptions: {
                    nodeTransforms: [localizedTemplateNodes],
                },
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
