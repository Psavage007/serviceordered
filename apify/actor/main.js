/**
 * ServiceOrdered — Apify Google Maps Scraper Actor
 *
 * Loops through every service category + city combination,
 * scrapes all matching GMB profiles, and pushes results to
 * the ServiceOrdered webhook for automatic database import.
 *
 * Uses: apify/google-maps-scraper as the underlying scraper.
 */

import Apify from 'apify';

const { Actor, log } = Apify;

await Actor.init();

const input = await Actor.getInput() ?? {};

// ── Default Data ──────────────────────────────────────────────────────────

const DEFAULT_CATEGORIES = [
    'electrical contractor',
    'plumber',
    'HVAC contractor',
    'roofing contractor',
    'concrete contractor',
    'framing contractor',
    'drywall contractor',
    'painting contractor',
    'flooring contractor',
    'glazing contractor',
    'insulation contractor',
    'landscaping contractor',
    'excavation contractor',
    'structural steel contractor',
    'fire protection contractor',
    'demolition contractor',
    'paving contractor',
    'solar panel installer',
    'elevator company',
    'low voltage contractor',
    'trash compactor repair',
    'garage door repair',
    'septic tank service',
    'well drilling company',
    'chimney sweep',
    'foundation repair contractor',
    'waterproofing contractor',
    'wildlife removal service',
    'mold remediation contractor',
    'asbestos removal contractor',
    'radon mitigation contractor',
    'dock builder',
    'pool contractor',
    'awning installer',
    'sign installer',
    'window tinting service',
    'epoxy flooring contractor',
    'gutter installer',
    'stucco contractor',
    'fence contractor',
    'crane rental company',
    'scaffolding rental',
    'sandblasting service',
    'acoustic ceiling installer',
    'dumbwaiter installer',
    'commercial locksmith',
    'fire damper testing',
    'backflow preventer testing',
    'tank installation contractor',
    'hardscape contractor',
];

// Top 100 US cities
const DEFAULT_CITIES = [
    { name:'New York City',   state:'New York',       abbr:'NY' },
    { name:'Los Angeles',     state:'California',     abbr:'CA' },
    { name:'Chicago',         state:'Illinois',       abbr:'IL' },
    { name:'Houston',         state:'Texas',          abbr:'TX' },
    { name:'Phoenix',         state:'Arizona',        abbr:'AZ' },
    { name:'Philadelphia',    state:'Pennsylvania',   abbr:'PA' },
    { name:'San Antonio',     state:'Texas',          abbr:'TX' },
    { name:'San Diego',       state:'California',     abbr:'CA' },
    { name:'Dallas',          state:'Texas',          abbr:'TX' },
    { name:'San Jose',        state:'California',     abbr:'CA' },
    { name:'Austin',          state:'Texas',          abbr:'TX' },
    { name:'Jacksonville',    state:'Florida',        abbr:'FL' },
    { name:'Fort Worth',      state:'Texas',          abbr:'TX' },
    { name:'Columbus',        state:'Ohio',           abbr:'OH' },
    { name:'Charlotte',       state:'North Carolina', abbr:'NC' },
    { name:'Indianapolis',    state:'Indiana',        abbr:'IN' },
    { name:'San Francisco',   state:'California',     abbr:'CA' },
    { name:'Seattle',         state:'Washington',     abbr:'WA' },
    { name:'Denver',          state:'Colorado',       abbr:'CO' },
    { name:'Nashville',       state:'Tennessee',      abbr:'TN' },
    { name:'Oklahoma City',   state:'Oklahoma',       abbr:'OK' },
    { name:'El Paso',         state:'Texas',          abbr:'TX' },
    { name:'Washington',      state:'DC',             abbr:'DC' },
    { name:'Las Vegas',       state:'Nevada',         abbr:'NV' },
    { name:'Louisville',      state:'Kentucky',       abbr:'KY' },
    { name:'Memphis',         state:'Tennessee',      abbr:'TN' },
    { name:'Portland',        state:'Oregon',         abbr:'OR' },
    { name:'Baltimore',       state:'Maryland',       abbr:'MD' },
    { name:'Milwaukee',       state:'Wisconsin',      abbr:'WI' },
    { name:'Albuquerque',     state:'New Mexico',     abbr:'NM' },
    { name:'Tucson',          state:'Arizona',        abbr:'AZ' },
    { name:'Fresno',          state:'California',     abbr:'CA' },
    { name:'Sacramento',      state:'California',     abbr:'CA' },
    { name:'Mesa',            state:'Arizona',        abbr:'AZ' },
    { name:'Kansas City',     state:'Missouri',       abbr:'MO' },
    { name:'Atlanta',         state:'Georgia',        abbr:'GA' },
    { name:'Omaha',           state:'Nebraska',       abbr:'NE' },
    { name:'Colorado Springs',state:'Colorado',       abbr:'CO' },
    { name:'Raleigh',         state:'North Carolina', abbr:'NC' },
    { name:'Long Beach',      state:'California',     abbr:'CA' },
    { name:'Virginia Beach',  state:'Virginia',       abbr:'VA' },
    { name:'Minneapolis',     state:'Minnesota',      abbr:'MN' },
    { name:'Tampa',           state:'Florida',        abbr:'FL' },
    { name:'New Orleans',     state:'Louisiana',      abbr:'LA' },
    { name:'Arlington',       state:'Texas',          abbr:'TX' },
    { name:'Bakersfield',     state:'California',     abbr:'CA' },
    { name:'Honolulu',        state:'Hawaii',         abbr:'HI' },
    { name:'Anaheim',         state:'California',     abbr:'CA' },
    { name:'Aurora',          state:'Colorado',       abbr:'CO' },
    { name:'Santa Ana',       state:'California',     abbr:'CA' },
    { name:'Corpus Christi',  state:'Texas',          abbr:'TX' },
    { name:'Riverside',       state:'California',     abbr:'CA' },
    { name:'Lexington',       state:'Kentucky',       abbr:'KY' },
    { name:'St. Louis',       state:'Missouri',       abbr:'MO' },
    { name:'Pittsburgh',      state:'Pennsylvania',   abbr:'PA' },
    { name:'Stockton',        state:'California',     abbr:'CA' },
    { name:'Anchorage',       state:'Alaska',         abbr:'AK' },
    { name:'Cincinnati',      state:'Ohio',           abbr:'OH' },
    { name:'St. Paul',        state:'Minnesota',      abbr:'MN' },
    { name:'Greensboro',      state:'North Carolina', abbr:'NC' },
    { name:'Toledo',          state:'Ohio',           abbr:'OH' },
    { name:'Newark',          state:'New Jersey',     abbr:'NJ' },
    { name:'Plano',           state:'Texas',          abbr:'TX' },
    { name:'Henderson',       state:'Nevada',         abbr:'NV' },
    { name:'Orlando',         state:'Florida',        abbr:'FL' },
    { name:'Chandler',        state:'Arizona',        abbr:'AZ' },
    { name:'Laredo',          state:'Texas',          abbr:'TX' },
    { name:'Madison',         state:'Wisconsin',      abbr:'WI' },
    { name:'Durham',          state:'North Carolina', abbr:'NC' },
    { name:'Lubbock',         state:'Texas',          abbr:'TX' },
    { name:'Winston-Salem',   state:'North Carolina', abbr:'NC' },
    { name:'Garland',         state:'Texas',          abbr:'TX' },
    { name:'Glendale',        state:'Arizona',        abbr:'AZ' },
    { name:'Hialeah',         state:'Florida',        abbr:'FL' },
    { name:'Reno',            state:'Nevada',         abbr:'NV' },
    { name:'Baton Rouge',     state:'Louisiana',      abbr:'LA' },
    { name:'Irvine',          state:'California',     abbr:'CA' },
    { name:'Chesapeake',      state:'Virginia',       abbr:'VA' },
    { name:'Scottsdale',      state:'Arizona',        abbr:'AZ' },
    { name:'North Las Vegas', state:'Nevada',         abbr:'NV' },
    { name:'Fremont',         state:'California',     abbr:'CA' },
    { name:'Gilbert',         state:'Arizona',        abbr:'AZ' },
    { name:'San Bernardino',  state:'California',     abbr:'CA' },
    { name:'Birmingham',      state:'Alabama',        abbr:'AL' },
    { name:'Rochester',       state:'New York',       abbr:'NY' },
    { name:'Richmond',        state:'Virginia',       abbr:'VA' },
    { name:'Spokane',         state:'Washington',     abbr:'WA' },
    { name:'Des Moines',      state:'Iowa',           abbr:'IA' },
    { name:'Montgomery',      state:'Alabama',        abbr:'AL' },
    { name:'Modesto',         state:'California',     abbr:'CA' },
    { name:'Fayetteville',    state:'North Carolina', abbr:'NC' },
    { name:'Tacoma',          state:'Washington',     abbr:'WA' },
    { name:'Shreveport',      state:'Louisiana',      abbr:'LA' },
    { name:'Fontana',         state:'California',     abbr:'CA' },
    { name:'Moreno Valley',   state:'California',     abbr:'CA' },
    { name:'Glendale',        state:'California',     abbr:'CA' },
    { name:'Akron',           state:'Ohio',           abbr:'OH' },
    { name:'Yonkers',         state:'New York',       abbr:'NY' },
    { name:'Huntington Beach',state:'California',     abbr:'CA' },
];

// ── Config ────────────────────────────────────────────────────────────────
const WEBHOOK_URL    = input.webhookUrl    || 'https://serviceordered.com/api/webhook.php';
const WEBHOOK_SECRET = input.webhookSecret || '';
const MAX_PER_SEARCH = input.maxPerSearch  || 20;
const CATEGORIES     = input.categories    || DEFAULT_CATEGORIES;
const CITIES         = input.cities        || DEFAULT_CITIES;
// ─────────────────────────────────────────────────────────────────────────

log.info(`Starting scrape: ${CATEGORIES.length} categories × ${CITIES.length} cities = ${CATEGORIES.length * CITIES.length} searches`);

const dataset = await Actor.openDataset();
let total_imported = 0;

for (const category of CATEGORIES) {
    for (const city of CITIES) {
        const search_term = `${category} in ${city.name}, ${city.state}`;
        log.info(`Searching: ${search_term}`);

        try {
            const run = await Actor.call('apify/google-maps-scraper', {
                searchStringsArray:       [search_term],
                maxCrawledPlacesPerSearch: MAX_PER_SEARCH,
                language:                 'en',
                includeReviews:           false,
                includeImages:            false,
                exportPlaceUrls:          true,
            }, { waitSecs: 300 });

            const { items } = await Actor.apifyClient
                .dataset(run.defaultDatasetId)
                .listItems({ limit: MAX_PER_SEARCH });

            const enriched = (items || []).map(item => ({
                ...item,
                _so_category:  category,
                _so_city:      city.name,
                _so_state:     city.state,
                _so_state_abbr:city.abbr,
            }));

            if (enriched.length > 0) {
                await dataset.pushData(enriched);
                await push_to_webhook(enriched, WEBHOOK_URL, WEBHOOK_SECRET);
                total_imported += enriched.length;
                log.info(`  → ${enriched.length} results for "${search_term}"`);
            }

        } catch (err) {
            log.error(`Failed: ${search_term} — ${err.message}`);
        }

        await sleep(2000);
    }
}

log.info(`Done. Total records scraped: ${total_imported}`);
await Actor.setValue('SUMMARY', { total_imported, categories: CATEGORIES.length, cities: CITIES.length });
await Actor.exit();

// ── Helpers ───────────────────────────────────────────────────────────────

async function push_to_webhook(items, url, secret) {
    const full_url = secret ? `${url}?token=${secret}` : url;
    const response = await fetch(full_url, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(items),
    });
    if (!response.ok) {
        log.warning(`Webhook returned ${response.status}`);
    }
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
