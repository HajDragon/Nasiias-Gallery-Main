import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

export type TranslationTree = {
    [key: string]: string | TranslationTree;
};

export type TranslationReplacements = Record<string, string | number>;

/**
 * Resolve a dot-notated translation key from a nested dictionary.
 */
function lookup(
    dictionary: TranslationTree | undefined,
    key: string,
): string | undefined {
    if (!dictionary) {
        return undefined;
    }

    let value: string | TranslationTree | undefined = dictionary;

    for (const segment of key.split('.')) {
        if (typeof value !== 'object' || value === null) {
            return undefined;
        }

        value = value[segment];
    }

    return typeof value === 'string' ? value : undefined;
}

/**
 * Replace `:placeholder` tokens with the provided values.
 */
function interpolate(
    value: string,
    replacements?: TranslationReplacements,
): string {
    if (!replacements) {
        return value;
    }

    return value.replace(/:([a-zA-Z0-9_]+)/g, (match, name: string) =>
        Object.prototype.hasOwnProperty.call(replacements, name)
            ? String(replacements[name])
            : match,
    );
}

/**
 * Translate interface strings using the dictionaries shared by Laravel.
 *
 * Missing keys fall back to the application's fallback locale before
 * returning the key itself, which keeps untranslated strings visible.
 */
export function useTranslation() {
    const { translations, fallbackTranslations } = usePage().props;

    const t = useCallback(
        (key: string, replacements?: TranslationReplacements): string => {
            const value =
                lookup(translations, key) ??
                lookup(fallbackTranslations, key) ??
                key;

            return interpolate(value, replacements);
        },
        [translations, fallbackTranslations],
    );

    return { t };
}
