export type Locale = 'id' | 'en';

export interface TranslationEntry {
  source: string;
  translation: string;
  reverse?: string;
}

export interface TemplateMatch {
  captures: string[];
  values: string[];
  segments: string[];
  translateFlags: boolean[];
  translation: string;
}

interface TextPart {
  type: 'text';
  value: string;
}

interface PlaceholderPart {
  type: 'placeholder';
  index: number;
  flags: string[];
}

type Part = TextPart | PlaceholderPart;

interface PlaceholderFlags {
  number: boolean;
  translate: boolean;
  singular: boolean;
}

interface CompiledTemplate {
  regex: RegExp;
  segments: string[];
  translateFlags: boolean[];
  singularFlags: boolean[];
  specificity: number;
}

const PLACEHOLDER_PATTERN = /\{(\d+)((?::[a-z]+)*)\}/g;

const normalize = (value: string) => value.replace(/\s+/g, ' ').trim();
const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const isPlaceholder = (part: Part): part is PlaceholderPart => part.type === 'placeholder';

const flagsOf = (part: PlaceholderPart): PlaceholderFlags => ({
  number: part.flags.includes('number'),
  translate: part.flags.includes('translate'),
  singular: part.flags.includes('singular'),
});

const parseParts = (value: string): Part[] => {
  const parts: Part[] = [];
  let lastIndex = 0;
  let match: RegExpExecArray | null;

  PLACEHOLDER_PATTERN.lastIndex = 0;

  while ((match = PLACEHOLDER_PATTERN.exec(value)) !== null) {
    if (match.index > lastIndex) {
      parts.push({ type: 'text', value: value.slice(lastIndex, match.index) });
    }

    parts.push({
      type: 'placeholder',
      index: Number(match[1]),
      flags: match[2].split(':').filter(Boolean),
    });

    lastIndex = PLACEHOLDER_PATTERN.lastIndex;
  }

  if (lastIndex < value.length) {
    parts.push({ type: 'text', value: value.slice(lastIndex) });
  }

  return parts;
};

const serializeParts = (parts: Part[], flagsByIndex?: Map<number, string[]>) => parts
  .map((part) => {
    if (part.type !== 'placeholder') return part.value;

    const flags = flagsByIndex?.get(part.index) ?? [];

    return `{${part.index}${flags.map((flag) => `:${flag}`).join('')}}`;
  })
  .join('');

const compileTemplate = (
  source: string,
  translation: string,
  options?: {
    sourceFlags?: Map<number, string[]>;
    translationFlags?: Map<number, string[]>;
  },
): CompiledTemplate | null => {
  const sourceParts = parseParts(source);
  const translationParts = parseParts(translation);
  const sourcePlaceholders = sourceParts.filter(isPlaceholder);
  const translationPlaceholders = translationParts.filter(isPlaceholder);

  if (sourcePlaceholders.length === 0) return null;
  if (sourcePlaceholders.length !== translationPlaceholders.length) return null;

  const sourceIndices = sourcePlaceholders.map((part) => part.index);
  const translationIndices = translationPlaceholders.map((part) => part.index);

  if (sourceIndices.join(',') !== translationIndices.join(',')) return null;
  if (new Set(sourceIndices).size !== sourceIndices.length) return null;

  const sourceFlagsByIndex = options?.sourceFlags ?? new Map(
    sourcePlaceholders.map((part) => [part.index, part.flags]),
  );
  const translationFlagsByIndex = options?.translationFlags ?? new Map(
    translationPlaceholders.map((part) => [part.index, part.flags]),
  );

  const regex = new RegExp(`^${sourceParts
    .map((part) => {
      if (part.type !== 'placeholder') return escapeRegExp(part.value);

      const flags = flagsOf({
        type: 'placeholder',
        index: part.index,
        flags: sourceFlagsByIndex.get(part.index) ?? [],
      });

      return flags.number ? '(-?\\d[\\d.,]*)' : '(.+?)';
    })
    .join('')}$`);

  const segments: string[] = [];
  let current = '';

  for (const part of translationParts) {
    if (part.type === 'placeholder') {
      segments.push(current);
      current = '';
    } else {
      current += part.value;
    }
  }

  segments.push(current);

  return {
    regex,
    segments,
    translateFlags: sourceIndices.map((index) => (
      flagsOf({ type: 'placeholder', index, flags: translationFlagsByIndex.get(index) ?? [] }).translate
    )),
    singularFlags: sourceIndices.map((index) => (
      flagsOf({ type: 'placeholder', index, flags: translationFlagsByIndex.get(index) ?? [] }).singular
    )),
    specificity: sourceParts
      .filter((part): part is TextPart => part.type === 'text')
      .reduce((total, part) => total + part.value.length, 0),
  };
};

const singularUnits: Record<string, string> = {
  days: 'day',
  weeks: 'week',
  months: 'month',
  years: 'year',
  products: 'product',
  items: 'item',
  transfers: 'transfer',
  stores: 'store',
  warehouses: 'warehouse',
  rows: 'row',
  orders: 'order',
  units: 'unit',
  transactions: 'transaction',
  customers: 'customer',
  records: 'record',
  menus: 'menu',
  times: 'time',
  codes: 'code',
  points: 'point',
  locations: 'location',
  users: 'user',
  roles: 'role',
  vouchers: 'voucher',
  requests: 'request',
  documents: 'document',
  exports: 'export',
  reservations: 'reservation',
  labels: 'label',
  skus: 'SKU',
  statuses: 'status',
  categories: 'category',
  segments: 'segment',
  channels: 'channel',
  brands: 'brand',
  suppliers: 'supplier',
  partners: 'partner',
  memberships: 'membership',
  benefits: 'benefit',
  conditions: 'condition',
  couriers: 'courier',
  promos: 'promo',
  discounts: 'discount',
  banners: 'banner',
  specifications: 'specification',
  rooms: 'room',
  racks: 'rack',
  prices: 'price',
  permissions: 'permission',
  rewards: 'reward',
  mutations: 'mutation',
};

const singularizeLabel = (value: string) => {
  const match = value.match(/^([A-Za-z]+)(.*)$/);

  if (!match) return value;

  const singular = singularUnits[match[1].toLowerCase()];

  if (!singular) return value;

  const translated = match[1][0] === match[1][0].toUpperCase()
    ? `${singular[0].toUpperCase()}${singular.slice(1)}`
    : singular;

  return `${translated}${match[2]}`;
};

const singularizeLeadingUnit = (segment: string) => segment.replace(
  /^(\s*)([A-Za-z]+)\b/,
  (full, space: string, word: string) => {
    const singular = singularUnits[word.toLowerCase()];

    if (!singular) return full;

    const value = word[0] === word[0].toUpperCase()
      ? `${singular[0].toUpperCase()}${singular.slice(1)}`
      : singular;

    return `${space}${value}`;
  },
);

const listingUnits: Record<string, string> = {
  baris: 'rows',
  customer: 'customers',
  data: 'records',
  export: 'exports',
  order: 'orders',
  retur: 'returns',
  transaksi: 'transactions',
  user: 'users',
  dokumen: 'documents',
  produk: 'products',
  request: 'requests',
  role: 'roles',
  voucher: 'vouchers',
  toko: 'stores',
  gudang: 'warehouses',
  item: 'items',
  pesanan: 'orders',
};

const listingUnitPattern = Object.keys(listingUnits).join('|');

const buildListingMatch = (value: string, target: Locale): TemplateMatch | null => {
  if (target === 'en') {
    const match = value.match(
      new RegExp(`^Menampilkan\\s+(.+?)\\s*-\\s*(.+?)\\s+dari\\s+(.+?)\\s+(${listingUnitPattern})$`, 'i'),
    );

    if (!match) return null;

    const unit = listingUnits[match[4].toLowerCase()];
    const captures = [match[1], match[2], match[3]];

    return {
      captures,
      values: captures,
      segments: ['Showing ', ' - ', ' of ', ` ${unit}`],
      translateFlags: [false, false, false],
      translation: `Showing ${match[1]} - ${match[2]} of ${match[3]} ${unit}`,
    };
  }

  const englishUnits = Object.values(listingUnits).join('|');
  const match = value.match(
    new RegExp(`^Showing\\s+(.+?)\\s*-\\s*(.+?)\\s+of\\s+(.+?)\\s+(${englishUnits})$`, 'i'),
  );

  if (!match) return null;

  const unit = Object.entries(listingUnits).find(([, english]) => english === match[4].toLowerCase());

  if (!unit) return null;

  const captures = [match[1], match[2], match[3]];

  return {
    captures,
    values: captures,
    segments: ['Menampilkan ', ' - ', ' dari ', ` ${unit[0]}`],
    translateFlags: [false, false, false],
    translation: `Menampilkan ${match[1]} - ${match[2]} dari ${match[3]} ${unit[0]}`,
  };
};

const capitalize = (value: string) => `${value[0]?.toUpperCase() ?? ''}${value.slice(1)}`;

const translateCapture = (
  capture: string,
  target: Locale,
  exactMap: Map<string, string>,
) => {
  const normalized = normalize(capture);
  const exact = exactMap.get(normalized);

  if (exact !== undefined) return exact;

  if (target === 'id') {
    for (const [plural, singular] of Object.entries(singularUnits)) {
      const candidates: string[] = [];

      if (singular.toLowerCase() === normalized.toLowerCase()) {
        candidates.push(plural, capitalize(plural));
      }

      if (plural.toLowerCase() === normalized.toLowerCase()) {
        candidates.push(singular, capitalize(singular));
      }

      for (const candidate of candidates) {
        const translated = exactMap.get(candidate);

        if (translated !== undefined) return translated;
      }
    }
  }

  return capture;
};

const buildMatch = (
  template: CompiledTemplate,
  captures: string[],
  target: Locale,
  exactMap: Map<string, string>,
): TemplateMatch => {
  const values = captures.map((capture, index) => {
    let value = capture;

    if (template.translateFlags[index]) {
      value = translateCapture(capture, target, exactMap);
    }

    if (target === 'en' && template.singularFlags[index]) {
      value = singularizeLabel(value);
    }

    return value;
  });

  const segments = template.segments.slice();

  if (target === 'en') {
    for (let index = 0; index < values.length; index += 1) {
      if (!template.translateFlags[index] && values[index] === '1') {
        segments[index + 1] = singularizeLeadingUnit(segments[index + 1]);
      }
    }
  }

  let translation = segments[0] ?? '';

  for (let index = 0; index < values.length; index += 1) {
    translation += `${values[index]}${segments[index + 1] ?? ''}`;
  }

  return {
    captures,
    values,
    segments,
    translateFlags: template.translateFlags,
    translation,
  };
};

export const createTranslator = (
  entries: TranslationEntry[],
  reverseEntries: TranslationEntry[] = [],
) => {
  const forward = new Map<string, string>();
  const reverse = new Map<string, string>();
  const forwardTemplates: CompiledTemplate[] = [];
  const reverseTemplates: CompiledTemplate[] = [];

  for (const entry of entries) {
    const source = normalize(entry.source);
    const translation = normalize(entry.translation);
    const reverseValue = normalize(entry.reverse ?? entry.source);

    if (!source || !translation || source === translation) continue;
    if (source.includes('`') || translation.includes('`')) continue;
    if (source.includes('${') || translation.includes('${')) continue;

    const sourceParts = parseParts(source);

    if (sourceParts.some(isPlaceholder)) {
      const sourceFlags = new Map<number, string[]>();
      const translationFlags = new Map<number, string[]>();

      for (const part of sourceParts) {
        if (part.type === 'placeholder') {
          sourceFlags.set(part.index, part.flags);
        }
      }

      for (const part of parseParts(translation)) {
        if (part.type === 'placeholder') {
          translationFlags.set(part.index, part.flags);
        }
      }

      const forwardTemplate = compileTemplate(source, translation, { sourceFlags, translationFlags });

      if (forwardTemplate) {
        forwardTemplates.push(forwardTemplate);
      }

      const reverseTranslation = serializeParts(parseParts(entry.reverse ?? entry.source));
      const reverseSource = serializeParts(parseParts(translation), sourceFlags);
      const reverseTemplate = compileTemplate(reverseSource, reverseTranslation, {
        sourceFlags,
        translationFlags,
      });

      if (reverseTemplate) {
        reverseTemplates.push(reverseTemplate);
      }

      continue;
    }

    forward.set(source, translation);

    if (!reverse.has(translation) || entry.reverse) {
      reverse.set(translation, reverseValue);
    }
  }

  for (const entry of reverseEntries) {
    const source = normalize(entry.source);
    const translation = normalize(entry.translation);

    if (!source || !translation || source === translation) continue;

    reverse.set(source, translation);
  }

  const sortTemplates = (templates: CompiledTemplate[]) => templates.sort(
    (left, right) => right.specificity - left.specificity,
  );

  sortTemplates(forwardTemplates);
  sortTemplates(reverseTemplates);

  const mapFor = (target: Locale) => (target === 'en' ? forward : reverse);
  const templatesFor = (target: Locale) => (target === 'en' ? forwardTemplates : reverseTemplates);
  const translationCache = new Map<string, string | null>();

  const match = (value: string, target: Locale): TemplateMatch | undefined => {
    const normalized = normalize(value);

    if (!normalized) return undefined;

    const exactMap = mapFor(target);

    if (exactMap.has(normalized)) return undefined;

    const listing = buildListingMatch(normalized, target);

    if (listing) return listing;

    for (const template of templatesFor(target)) {
      const result = template.regex.exec(normalized);

      if (result) {
        return buildMatch(template, result.slice(1), target, exactMap);
      }
    }

    return undefined;
  };

  const translate = (value: string, target: Locale): string | undefined => {
    const normalized = normalize(value);

    if (!normalized) return undefined;

    const cacheKey = `${target}:${normalized}`;

    if (translationCache.has(cacheKey)) {
      return translationCache.get(cacheKey) ?? undefined;
    }

    const exact = mapFor(target).get(normalized);

    if (exact !== undefined) {
      translationCache.set(cacheKey, exact);

      return exact;
    }

    const translation = match(value, target)?.translation ?? null;

    translationCache.set(cacheKey, translation);

    return translation ?? undefined;
  };

  const exact = (value: string, target: Locale): string | undefined => mapFor(target).get(normalize(value));

  return {
    exact,
    match,
    translate,
  };
};
