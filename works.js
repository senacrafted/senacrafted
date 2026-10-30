// ─────────────────────────────────────────────────────────────
//  EDIT THIS FILE to update your site. No other file needs to change.
//
//  Photos: drop them in the  images/  folder and list them in
//  `photos`. The first photo is the one shown in the grid; the rest
//  show up when someone clicks the piece. Leave photos empty ([])
//  to show a placeholder tile.
//
//  description: use \n\n between paragraphs. Cards show the first few lines;
//               the full text shows when someone clicks the piece.
//
//  category: "leather" | "paper"
//  status:   "available" | "sold" | "custom" (made to order) | "showcase" (shown, not for sale)
//  price:    a number (7.25), a range ([10, 18]), or null to hide the price
//  buyLink:  paste the Etsy listing link to show a "Buy on Etsy" button.
//            Leave "" and visitors get an "Ask about it" email button instead.
// ─────────────────────────────────────────────────────────────

const SITE = {
  name: "Sena Crafted",
  tagline: "Nature paintings and handmade leather crafts — made with warmth, nostalgia, and a little bit of magic.",
  announcement: "Handmade in Georgia · Custom orders open",   // thin bar at the very top — leave "" to hide
  email: "senacrafted@gmail.com",
  instagram: "senacrafted",           // without the @ — leave "" to hide the button
  tiktok: "senacrafted",              // without the @ — leave "" to hide the button
  location: "Georgia, USA",
  // each "..." is its own paragraph
  about: [
    "Ever since I was a kid, I've loved creating with my hands. Growing up in Turkey, I knew I wanted to pursue my passion for art, so I came to the USA and studied Computer Animation! It taught me the professional pipeline and digital art, and now I channel what I've learned into leatherworking, painting, and drawing.",
    "My work draws on childhood memories, nature, and everyday life, so no two pieces are exactly alike. You'll find me at festivals and markets around Georgia, and if you see me, come say hi, or reach out for a custom piece!",
  ],
  custom: "I'm open to custom orders, so please reach out if you're thinking of something specific! Whether it's something I've made before or something unique, I'd be more than happy to bring your vision to life.",
  hello: "Questions, comments, or just want to reach out?\nShoot me a message!",   // \n = new line
};

const WORKS = [
  // ── Leather ──
  { title: "Swan Leather Journal Cover — A5 Slim", category: "leather", price: 195, status: "available",
    photos: ["images/swan-cover-5.jpg", "images/swan-cover-1.jpg", "images/swan-cover-2.jpg", "images/swan-cover-3.jpg", "images/swan-cover-4.jpg"],
    description: "One of a kind handmade swan leather journal cover made by me! The swans are hand painted and hand stitched to the cover of the journal and are meant to complement each other like yin and yang. 🦢🧵\n\nThis piece is for someone who wants to have a journal cover that’s unique, just like any ideas and thoughts it’ll hold inside. It was made with love and care, and hopefully it’ll go to someone who will take it on adventures and create, while being inspired by life!",
    buyLink: "https://www.etsy.com/listing/4490436039/hand-painted-swan-leather-journal-cover" },
  { title: "Crow Keychain Pouch", category: "leather", price: [10, 18], status: "available",
    photos: ["images/crow-keychain-1.jpg", "images/crow-keychain-2.jpg", "images/crow-keychain-3.jpg", "images/crow-keychain-4.jpg", "images/owl-keychain-1.jpg"],
    description: "A tiny handmade crow friend made from leather. 🐦‍⬛\n\nThis little crow was designed to feel like a goofy friend, tagging along in your daily life. The big crow keychain was stitched to be a pouch so that, like every crow, he can store tiny treasures, coins, or even an AirTag!\n\nEach crow was handmade by me — every part was cut, painted and stitched by hand — so each one ends up with its own tiny personality, with color, paint and stitch variations.\n\nSmall $10, big $18.",
    buyLink: "https://www.etsy.com/listing/4503827555/handmade-leather-crow-keychain-pouch" },
  { title: "Red Leather Journal Cover — A5 Slim", category: "leather", price: 70, status: "available",
    photos: ["images/red-journal-cover-1.jpg", "images/red-journal-cover-2.jpg", "images/red-journal-cover-3.jpg", "images/journal-cover.jpg"],
    description: "Each cover is made to order, with leather and hand assembled by me! I wanted these journals to be durable, reusable and imperfect, something to grow and accompany its user through life and show character with every scratch and dent.\n\nThe slim wrap/fold is to ensure any notebook inside is protected, and the elastic closure keeps everything secure, so feel free to toss it in your bag or take it on an adventure!\n\nThis one is the A5 slim in red. Also available in A6 slim, and in brown or black.\n\nSince each piece is unique and real leather is used, natural variations in color, texture, markings, and aging are expected, but that’s what makes each one unique!",
    buyLink: "https://www.etsy.com/listing/4503809793/refillable-leather-journal-cover-a5-slim" },
  { title: "Brown Leather Traveler's Notebook — A6 Slim", category: "leather", price: 40, status: "available",
    photos: ["images/traveler-notebook-1.jpg", "images/traveler-notebook-2.jpg", "images/traveler-notebook-3.jpg", "images/traveler-notebook-4.jpg"],
    description: "Each cover is made to order, with leather and hand assembled by me! I wanted these journals to be durable, reusable and imperfect, something to grow and accompany its user through life and show character with every scratch and dent.\n\nThe slim wrap/fold is to ensure any notebook inside is protected, and the elastic closure keeps everything secure, so feel free to toss it in your bag or take it on an adventure!\n\nThis one is the A6 slim in brown. Also available in A5 slim, and in red or black.\n\nSince each piece is unique and real leather is used, natural variations in color, texture, markings, and aging are expected, but that’s what makes each one unique!",
    buyLink: "https://www.etsy.com/listing/4503809793/refillable-leather-journal-cover-a5-slim" },
  { title: "Running Man Journal Cover — A6 Slim", category: "leather", price: null, status: "available",
    photos: ["images/running-man-cover-1.jpg"],
    description: "A custom A6 slim leather journal cover with the famous running man statue seen all around Sarasota, Florida! ☀️", buyLink: "" },
  { title: "Slim Leather Wallet", category: "leather", price: null, status: "available",
    photos: ["images/leather-wallet.jpg"],
    description: "Slim brown leather wallet, hand stitched, holds two cards at the front and a few in the middle flap.", buyLink: "" },

  { title: "Handmade Stingray Leather Bag", category: "leather", price: null, status: "showcase",
    photos: ["images/stingray-bag-1.jpg", "images/stingray-bag-2.jpg", "images/stingray-bag-3.jpg", "images/stingray-bag-4.jpg"],
    description: "This was the first time I worked with an exotic type of leather, and I decided not to waste a single part and to showcase it in its full beauty! The process took quite a lot of effort to hand stitch and taught me a lot, but it was all worth the work!", buyLink: "" },

  // ── Paintings, prints & paper goods ──
  { title: "Weeping Willow Postcard", category: "paper", price: 4.99, status: "available",
    photos: ["images/willow-postcard-full.jpg", "images/willow-postcard-2.jpg"],
    description: "A somber willow postcard. For moments that feel emotionally heavy, this is the perfect postcard. 🍃\n\nFor a cozy moment, a thoughtful moment, or during an adventure, these three postcards are a great way to reach out to friends, family, or anyone!\n\nA5 size 📄",
    buyLink: "https://www.etsy.com/listing/4503837941/illustrated-postcards-cozy-animals" },
  { title: "Adventure Awaits Postcard", category: "paper", price: 4.99, status: "available",
    photos: ["images/adventure-postcard-1.jpg", "images/adventure-postcard-2.jpg", "images/postcards-all.jpg"],
    description: "On a road trip? What better way to let someone know than letting them in on the adventure! 🚗\n\nFor a cozy moment, a thoughtful moment, or during an adventure, these three postcards are a great way to reach out to friends, family, or anyone!\n\nA5 size 📄",
    buyLink: "https://www.etsy.com/listing/4503837941/illustrated-postcards-cozy-animals" },
  { title: "Stay Cozy Postcard", category: "paper", price: 4.99, status: "available",
    photos: ["images/cozy-postcard-full.jpg", "images/cozy-postcard-2.jpg", "images/postcards-all.jpg"],
    description: "The best autumn, or autumn lover, postcard to send! A reminder to everyone to stay warm. 🐸\n\nFor a cozy moment, a thoughtful moment, or during an adventure, these three postcards are a great way to reach out to friends, family, or anyone!\n\nA5 size 📄",
    buyLink: "https://www.etsy.com/listing/4503837941/illustrated-postcards-cozy-animals" },
  { title: "Stronger Than You Think Card", category: "paper", price: 7.25, status: "available",
    photos: ["images/stronger-card-1.jpg", "images/stronger-card-2.jpg", "images/greeting-cards-1.jpg"],
    description: "For a special moment meant to remind someone how strong they truly are, this card is inspired by ants, who can carry 10 to 50 times their body weight! 🐜🐜🐜🐜🍎\n\nIf you too want to encourage, motivate and inspire someone, this card is for you!\n\nA5 size 📄",
    buyLink: "https://www.etsy.com/listing/4494733495/ants-stronger-than-you-think" },
  { title: "Otterly in Love Card", category: "paper", price: 7.25, status: "available",
    photos: ["images/congrats-card-1.jpg", "images/congrats-card-2.jpg", "images/greeting-cards-1.jpg"],
    description: "For an otterly comical congratulations, or if you simply love otters, this card is for you! 🦦🪨💙\n\nInspired by otters that never let their partners go, this card is meant to put a smile on your face and celebrate a special occasion!\n\nA5 size 📄",
    buyLink: "https://www.etsy.com/listing/4494730109/otters-otterly-in-love-wedding" },
  { title: "Monstera Shadow Painting", category: "paper", price: null, status: "available",
    photos: ["images/monstera-shadow-1.jpg", "images/monstera-shadow-2.jpg"],
    description: "Soft grey and blue monstera leaf shadows painted on a warm, textured canvas.\n\nOil on 18\" × 20\" canvas.", buyLink: "" },
  { title: "Monstera Shadow Painting — Muğla Rug Pattern", category: "paper", price: null, status: "available",
    photos: ["images/monstera-mugla-1.jpg", "images/monstera-mugla-2.jpg"],
    description: "Deep blue monstera leaves painted over a traditional Turkish rug pattern from Muğla.\n\nOil on 24\" × 36\".", buyLink: "" },
  { title: "Into the Garden Sticker Sheet", category: "paper", price: null, status: "available",
    photos: ["images/sticker-sheet-2.jpg", "images/sticker-sheet-1.jpg", "images/animal-sticker-pack.jpg"],
    description: "A sticker pack of combined illustrations, featuring animals and nature.", buyLink: "" },
];

// Upcoming festivals & markets. Remove past ones as you go.
// Example: { name: "Fall Art Market", date: "Nov 15", place: "Athens, GA", link: "https://..." },
const EVENTS = [];
