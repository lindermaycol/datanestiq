/**
 * Normalizes a tag label to a clean, URL-safe slug.
 * Examples:
 *   "Business Case" -> "business-case"
 *   "Educación / DRE" -> "educacion-dre"
 *   "Sector Público" -> "sector-publico"
 *   "sector-público" -> "sector-publico"
 */
export function slugifyTag(tag: string): string {
  if (!tag) return '';
  return tag
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '') // Remove accents / diacritics
    .replace(/[^a-z0-9\s-]/g, '') // Remove special characters except spaces and hyphens
    .trim()
    .replace(/\s+/g, '-'); // Replace spaces with single hyphens
}

/**
 * Returns a human-friendly label for a tag slug by looking up the original post tags.
 */
export function getTagLabelFromSlug(slug: string, posts: any[]): string {
  for (const post of posts) {
    const tags = post.data?.tags || [];
    for (const tag of tags) {
      if (slugifyTag(tag) === slug) {
        return tag;
      }
    }
  }
  return slug
    .split('-')
    .map(w => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ');
}
