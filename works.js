// ─────────────────────────────────────────────────────────────
//  EDIT THIS FILE to update your site. No other file needs to change.
//
//  Photos: drop them in the  images/  folder and put the file name
//  in `image` (e.g. image: "images/blue-wallet.jpg").
//  Leave image empty ("") to show a placeholder tile.
//
//  status:   "available" | "sold" | "custom"   (custom = made to order)
//  buyLink:  paste a Square / Stripe / Etsy checkout link to show a
//            "Buy now" button. Leave "" and visitors get an
//            "Ask about this piece" email button instead.
// ─────────────────────────────────────────────────────────────

const SITE = {
  name: "Sena Crafted",
  tagline: "Nature paintings and handmade leather crafts — made with warmth, nostalgia, and a little bit of magic.",
  email: "senacrafted@gmail.com",
  instagram: "senagazioglu",          // without the @ — leave "" to hide the button
  location: "Georgia, USA",
  about:
    "Hi, I'm Sena Gazioglu. I paint the natural world — willows, gardens, and the little animals that live in them — and I make leather goods by hand: journal covers, card holders, and patches cut, stitched, and painted one at a time. My work draws on childhood memories, nature, and everyday life, so no two pieces are exactly alike. You'll find me at festivals and markets around Georgia — come say hi, or reach out for a custom piece.",
};

const WORKS = [
  { title: "Swan Leather Book Cover", category: "leather", price: 195, status: "available",
    image: "images/swan-book-cover.jpg",
    description: "Black leather journal cover with a hand-stitched, hand-painted pair of swans — one white, one black — and an elastic closure.", buyLink: "" },
  { title: "Leather Journal Cover — Flower", category: "leather", price: 49.99, status: "available",
    image: "images/journal-cover-flower.jpg",
    description: "Tan leather panel with a stitched navy flower, framed in green leather and scattered gold stars.", buyLink: "" },
  { title: "Leather Journal Cover", category: "leather", price: 49.99, status: "available",
    image: "images/journal-cover.jpg",
    description: "A simple, handmade leather cover for your favorite journal or sketchbook.", buyLink: "" },
  { title: "Leather Flower Patch", category: "leather", price: 49.99, status: "available",
    image: "images/leather-flower-patch.jpg",
    description: "Red leather blooms on a cream leather base, hand-stitched with contrast thread.", buyLink: "" },
  { title: "Leather Card Holder", category: "leather", price: 49.99, status: "available",
    image: "images/leather-wallet.jpg",
    description: "Slim brown leather card sleeve, hand-stitched — fits a few cards in your front pocket.", buyLink: "" },
  { title: "Weeping Willow Postcard", category: "painting", price: 4.5, status: "available",
    image: "images/weeping-willow-postcard.jpg",
    description: "“Why does the willow weep?” A stamp-style postcard print.", buyLink: "" },
  { title: "Greetings from Georgia Print", category: "painting", price: 19.99, status: "available",
    image: "images/illustration-print.jpg",
    description: "A nostalgic road-trip illustration print — palms, a little red car, and two friends on the move.", buyLink: "" },
  { title: "Animal Sticker Pack", category: "painting", price: 8, status: "available",
    image: "images/animal-sticker-pack.jpg",
    description: "Cats, mice, a bunny, a busy ant, a weeping willow, and houseplants — illustrated stickers for planners and journals.", buyLink: "" },
  { title: "Illustrated Greeting Cards", category: "painting", price: 14.99, status: "available",
    image: "images/greeting-cards.jpg",
    description: "Sweet illustrated cards for weddings, birthdays, and celebrations.", buyLink: "" },
  { title: "Handmade Sketchbook", category: "painting", price: 29.99, status: "available",
    image: "images/sketchbook.jpg",
    description: "A hand-bound sketchbook with a hand-painted bunny cover.", buyLink: "" },
];

// Upcoming festivals & markets. Remove past ones as you go.
// Example: { name: "Fall Art Market", date: "Nov 15", place: "Athens, GA", link: "https://..." },
const EVENTS = [];
