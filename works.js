// ─────────────────────────────────────────────────────────────
//  EDIT THIS FILE to update your site. No other file needs to change.
//
//  Photos: drop them in the  images/  folder and list them in
//  `photos`. The first photo is the one shown in the grid; the rest
//  show up when someone clicks the piece. Leave photos empty ([])
//  to show a placeholder tile.
//
//  category: "leather" | "paper"
//  status:   "available" | "sold" | "custom"   (custom = made to order)
//  price:    a number, or null to hide the price
//  buyLink:  paste a Square / Stripe / Etsy checkout link to show a
//            "Buy now" button. Leave "" and visitors get an
//            "Ask about this piece" email button instead.
// ─────────────────────────────────────────────────────────────

const SITE = {
  name: "Sena Crafted",
  tagline: "Nature paintings and handmade leather crafts — made with warmth, nostalgia, and a little bit of magic.",
  announcement: "Handmade in Georgia · Custom orders open",   // thin bar at the very top — leave "" to hide
  email: "senacrafted@gmail.com",
  instagram: "senagazioglu",          // without the @ — leave "" to hide the button
  location: "Georgia, USA",
  about:
    "Hi, I'm Sena Gazioglu. I paint the natural world — willows, gardens, and the little animals that live in them — and I make leather goods by hand: journal covers, card holders, and patches cut, stitched, and painted one at a time. My work draws on childhood memories, nature, and everyday life, so no two pieces are exactly alike. You'll find me at festivals and markets around Georgia — come say hi, or reach out for a custom piece.",
};

const WORKS = [
  // ── Leather ──
  { title: "Swan Leather Book Cover", category: "leather", price: 195, status: "available",
    photos: ["images/swan-cover-1.jpg", "images/swan-cover-2.jpg", "images/swan-cover-3.jpg", "images/swan-cover-4.jpg", "images/swan-cover-5.jpg"],
    description: "Black leather book cover with a hand-stitched, hand-painted pair of swans — one white, one black — and an elastic closure. The swan outlines show through on the inside.", buyLink: "" },
  { title: "Crow Keychain", category: "leather", price: null, status: "available",
    photos: ["images/crow-keychain-2.jpg", "images/crow-keychain-1.jpg", "images/crow-keychain-3.jpg", "images/crow-keychain-4.jpg", "images/owl-keychain-1.jpg"],
    description: "A little black leather crow with big painted eyes and a leather wing tag, hand-stitched and clipped to a keyring. Each one has its own expression.", buyLink: "" },
  { title: "Red Leather Journal Cover", category: "leather", price: 49.99, status: "available",
    photos: ["images/red-journal-cover-1.jpg", "images/red-journal-cover-2.jpg", "images/red-journal-cover-3.jpg", "images/journal-cover.jpg"],
    description: "A bright red wraparound leather cover with a fold-over flap and elastic closure, made to hold your favorite notebook.", buyLink: "" },
  { title: "Brown Leather Traveler's Notebook", category: "leather", price: null, status: "available",
    photos: ["images/traveler-notebook-1.jpg", "images/traveler-notebook-2.jpg", "images/traveler-notebook-3.jpg", "images/traveler-notebook-4.jpg"],
    description: "Soft brown leather traveler's notebook with an elastic band that holds slim notebooks inside. Small enough for a bag or coat pocket.", buyLink: "" },
  { title: "Yellow Bird Journal Cover", category: "leather", price: null, status: "available",
    photos: ["images/bird-journal-cover-1.jpg"],
    description: "Brown leather journal cover with a stitched mustard-yellow bird and a little red checkered detail.", buyLink: "" },
  { title: "Leather Journal Cover — Flower", category: "leather", price: 49.99, status: "available",
    photos: ["images/journal-cover-flower.jpg"],
    description: "Tan leather panel with a stitched navy flower, framed in green leather and scattered gold stars.", buyLink: "" },
  { title: "Leather Flower Patch", category: "leather", price: 49.99, status: "available",
    photos: ["images/leather-flower-patch.jpg"],
    description: "Red leather blooms on a cream leather base, hand-stitched with contrast thread.", buyLink: "" },
  { title: "Leather Card Holder", category: "leather", price: 49.99, status: "available",
    photos: ["images/leather-wallet.jpg"],
    description: "Slim brown leather card sleeve, hand-stitched — fits a few cards in your front pocket.", buyLink: "" },

  // ── Paintings, prints & paper goods ──
  { title: "Into the Garden Sticker Sheet", category: "paper", price: 8, status: "available",
    photos: ["images/sticker-sheet-2.jpg", "images/sticker-sheet-1.jpg", "images/animal-sticker-pack.jpg"],
    description: "Cats, mice, a bunny, a busy ant, a weeping willow, and houseplants — illustrated stickers for planners and journals.", buyLink: "" },
  { title: "Weeping Willow Postcard", category: "paper", price: 4.5, status: "available",
    photos: ["images/willow-postcard-1.jpg", "images/willow-postcard-2.jpg"],
    description: "“Why does the willow weep?” A stamp-style postcard print.", buyLink: "" },
  { title: "Adventure Awaits Postcard", category: "paper", price: null, status: "available",
    photos: ["images/adventure-postcard-1.jpg", "images/adventure-postcard-2.jpg", "images/postcards-all.jpg"],
    description: "Two friends packing up the little red car for a road trip under the palm trees.", buyLink: "" },
  { title: "Stay Cozy Postcard", category: "paper", price: null, status: "available",
    photos: ["images/cozy-postcard-1.jpg", "images/cozy-postcard-2.jpg", "images/postcards-all.jpg"],
    description: "A frog wrapped in a blanket, toasting marshmallows by the campfire.", buyLink: "" },
  { title: "Illustrated Greeting Cards", category: "paper", price: 14.99, status: "available",
    photos: ["images/greeting-cards-1.jpg", "images/stronger-card-1.jpg", "images/stronger-card-2.jpg", "images/congrats-card-1.jpg", "images/congrats-card-2.jpg"],
    description: "Folded cards with a little message inside — “Stronger than you think” with an ant and her apple, and a pair of bears for weddings and congratulations.", buyLink: "" },
  { title: "Greetings from Georgia Print", category: "paper", price: 19.99, status: "available",
    photos: ["images/illustration-print.jpg"],
    description: "A nostalgic road-trip illustration print — palms, a little red car, and two friends on the move.", buyLink: "" },
  { title: "Handmade Sketchbook", category: "paper", price: 29.99, status: "available",
    photos: ["images/sketchbook.jpg"],
    description: "A hand-bound sketchbook with a hand-painted bunny cover.", buyLink: "" },
];

// Upcoming festivals & markets. Remove past ones as you go.
// Example: { name: "Fall Art Market", date: "Nov 15", place: "Athens, GA", link: "https://..." },
const EVENTS = [];
