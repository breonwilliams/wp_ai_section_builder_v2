# AI Section Mapping Instructions

## Overview
This document provides comprehensive instructions for the AI Document Processor to understand and map content from Word documents to the appropriate sections in the AI Section Builder plugin. Each section has specific fields, use cases, and mapping rules that must be followed.

## Table of Contents
1. [Hero Section](#1-hero-section)
2. [Hero-Form Section](#2-hero-form-section)
3. [Features Section](#3-features-section)
4. [Checklist Section](#4-checklist-section)
5. [FAQ Section](#5-faq-section)
6. [Stats Section](#6-stats-section)
7. [Testimonials Section](#7-testimonials-section)

---

## 1. HERO SECTION

### Complete Field Structure

**Content Fields:**
- `eyebrow_heading` (string) - Subheading/tagline text above main heading
- `heading` (string) - Main headline/title
- `content` (HTML string) - Body content paragraphs wrapped in `<p>` tags
- `outro_content` (HTML string) - Optional closing paragraph(s) below buttons

**Media Fields:**
- `media_type` (enum) - Options: 'none', 'image', 'video'
- `featured_image` (URL string) - Image URL when media_type is 'image'
- `video_url` (URL string) - Video URL when media_type is 'video' (supports YouTube)

**Repeatable Elements:**
- `global_blocks` (array) - Array of button objects, each containing:
  - `type`: 'button'
  - `id`: Unique identifier (e.g., 'btn_1')
  - `text`: Button label
  - `url`: Button link (default to '#' if not specified)
  - `style`: 'primary' or 'secondary'
  - `target`: '_self' or '_blank' (optional)

**Variant Options:**
- `theme_variant` (enum) - Options: 'light', 'dark'
- `layout_variant` (enum) - Options: 'content-left', 'content-right', 'center'

**Legacy Fields (maintain but leave empty):**
- `primary_cta_label` (string) - Empty string
- `primary_cta_url` (string) - Empty string
- `secondary_cta_label` (string) - Empty string
- `secondary_cta_url` (string) - Empty string

### AI Instructions for Hero Section

```
The Hero Section is a high-impact opening section designed to immediately capture attention with a strong headline and introductory content.

WHEN TO USE:
- TYPICALLY as the first section (90% of cases) when document starts with a main title
- When you have a strong headline with supporting introduction
- When content needs a compelling opener with clear messaging
- Can be used mid-page for major topic transitions or new chapter beginnings

CONTENT IDENTIFICATION PATTERNS:
- Document titles or main headings (H1)
- Opening paragraphs that introduce or summarize the topic
- Taglines or subheadings near the title (before or after main heading)
- Service businesses: "[Company Name] + [Service Type]", "Professional [Service] in [City]"
- SaaS/Tech: "The future of...", "Transform your...", "All-in-one..."
- E-commerce: Product name with benefit statement
- Value propositions: "We help...", "Our mission...", "Dedicated to..."
- Early mentions of CTAs (see CTA patterns below)

FIELD MAPPING RULES:
- eyebrow_heading: Subheadings, taglines, or text that appears above/near the main title
- heading: The primary headline - keep it concise and impactful
- content: Introduction paragraphs (2-3 paragraphs ideal). Each paragraph MUST be wrapped in <p> tags
- outro_content: Secondary information, disclaimers, or bridge text to the next section
- global_blocks: Create buttons for explicit CTAs. Default to 1-2 buttons max unless more are clearly specified
  - Primary CTAs: "Free Consultation", "Get Quote", "Start Free Trial", "Book Demo", "Get Started"
  - Secondary CTAs: "Learn More", "View Services", "See How It Works", "Watch Demo"

MEDIA HANDLING:
- media_type: Set to 'none' by default
- Only use 'image' or 'video' if document explicitly references visual content for this section
- Leave featured_image and video_url empty - these will be added by users later

VARIANT SELECTION LOGIC:
- theme_variant: Start with 'dark' if it's the first section, otherwise alternate with previous section
- layout_variant: Default to 'content-left' for text-heavy content, 'center' for shorter, impactful messages

QUALITY CHECKS:
- Ensure heading is not too long (ideal: 4-8 words)
- Content should provide clear value proposition or introduction
- Don't force a hero section if content doesn't warrant it
```

---

## 2. HERO-FORM SECTION

### Complete Field Structure

**Content Fields:**
- `eyebrow_heading` (string) - Subheading/tagline text above main heading
- `heading` (string) - Main headline/title
- `content` (HTML string) - Body content paragraphs wrapped in `<p>` tags
- `outro_content` (HTML string) - Optional closing paragraph(s) below buttons

**Form-Specific Fields:**
- `form_type` (enum) - Options: 'placeholder', 'shortcode'
- `form_shortcode` (string) - WordPress form shortcode (leave empty for user to add later)

**Repeatable Elements:**
- `global_blocks` (array) - Array of button objects (same structure as Hero)
  - `type`: 'button'
  - `id`: Unique identifier
  - `text`: Button label
  - `url`: Button link (default to '#')
  - `style`: 'primary' or 'secondary'
  - `target`: '_self' or '_blank' (optional)

**Variant Options:**
- `theme_variant` (enum) - Options: 'light', 'dark'
- `layout_variant` (enum) - Options: 'content-left', 'content-right', 'center'

**Legacy Fields (maintain but leave empty):**
- `primary_cta_label` (string) - Empty string
- `primary_cta_url` (string) - Empty string
- `secondary_cta_label` (string) - Empty string
- `secondary_cta_url` (string) - Empty string

**Note:** NO media fields (no featured_image, media_type, or video_url)

### AI Instructions for Hero-Form Section

```
The Hero-Form Section combines a hero-style introduction with a form area, ideal for contact pages, lead generation, or sign-up flows.

WHEN TO USE:
- When document mentions "contact us", "get in touch", "request a quote", "get started"
- For content about signing up, registration, inquiries, or consultations
- When there's a clear call-to-action that requires user input or response
- Typically used as closing sections but can appear anywhere contact is emphasized
- Common at document end: "Ready to get started?", "Let's talk", "How can we help?"

CONTENT IDENTIFICATION PATTERNS:
- Contact-related headings: "Contact Us", "Get in Touch", "Reach Out", "Let's Connect"
- Service requests: "Request a Quote", "Get Your Free Estimate", "Schedule Service"
- Consultation offers: "Book Your Free Consultation", "Schedule a Call", "Free Assessment"
- Sign-up language: "Join Us", "Get Started", "Sign Up Today", "Create Account"
- Form-related content: "Fill out the form", "Submit your information", "Send us a message"
- Response promises: "We'll get back to you within...", "Expect a response..."
- Privacy mentions: "Your information is safe", "We respect your privacy"

INDUSTRY-SPECIFIC PATTERNS:
- Legal: "Free Case Evaluation", "Legal Consultation"
- Medical: "Book Appointment", "Schedule Visit"
- Home Services: "Get Free Estimate", "Schedule Service Call"
- SaaS: "Start Your Free Trial", "Request Demo"
- E-commerce: "Join Our Newsletter", "Get Updates"

FIELD MAPPING RULES:
- eyebrow_heading: Trust builders like "Free Quote", "No Obligation", "24/7 Support", "Quick Response"
- heading: Action-oriented headline (e.g., "Get Your Free Quote", "Let's Build Something Together")
- content: Brief explanation of what happens next, response time, or value of contacting
- outro_content: Privacy assurances, office hours, alternative contact methods
- form_type: ALWAYS set to 'placeholder'
- form_shortcode: ALWAYS leave empty (users add their plugin shortcode later)
- global_blocks: Alternative CTAs like "Call Us", "Live Chat", "View FAQ"

FORM HANDLING RULES:
- NEVER generate actual form HTML
- ALWAYS use form_type: 'placeholder'
- NEVER try to create form fields or input elements
- Leave form_shortcode empty for user configuration
- The form area is a visual placeholder only

VARIANT SELECTION LOGIC:
- theme_variant: Often 'dark' for emphasis, but alternate with previous section
- layout_variant: 
  - 'content-left' = form on right (most common)
  - 'content-right' = form on left
  - 'center' = stacked layout for mobile-first approach

QUALITY CHECKS:
- Heading should be action-oriented and create urgency
- Content should explain value of contacting or what to expect
- Don't use if there's no clear contact/form context in the content
- Ensure it doesn't duplicate a regular Hero section
```

---

## 3. FEATURES SECTION

### Complete Field Structure

**Content Fields:**
- `eyebrow_heading` (string) - Subheading/tagline text above main heading
- `heading` (string) - Main headline/title (default: "Our Features")
- `content` (HTML string) - Introduction paragraphs wrapped in `<p>` tags
- `outro_content` (HTML string) - Optional closing paragraph(s)

**Media Fields:**
- `media_type` (enum) - Options: 'none', 'image', 'video'
- `featured_image` (URL string) - Image URL when media_type is 'image'
- `video_url` (URL string) - Video URL when media_type is 'video'

**Repeatable Elements:**
- `cards` (array) - Array of feature/service cards, each containing:
  - `id`: Unique identifier (e.g., 'feature_1')
  - `image`: Card image URL (leave empty for icons later)
  - `heading`: Card title/feature name
  - `content`: Card description text
  - `link`: Optional link URL (default to empty)
  - `link_text`: Link label (e.g., "Learn More")
  - `link_target`: '_self' or '_blank'

- `global_blocks` (array) - Array of button objects (same as Hero)

**Variant Options:**
- `theme_variant` (enum) - Options: 'light', 'dark'
- `layout_variant` (enum) - Options: 'content-left', 'content-right', 'center'
- `card_alignment` (enum) - Options: 'left', 'center' (for card content alignment)

**Legacy Fields (maintain but leave empty):**
- `primary_cta_label`, `primary_cta_url`, `secondary_cta_label`, `secondary_cta_url`

### AI Instructions for Features Section

```
The Features Section is the most versatile section type, used to display services, features, benefits, offerings, or any grouped content in a card-based layout.

WHEN TO USE:
- Lists of services, features, benefits, or offerings
- "What We Do", "Our Services", "How It Works" content
- Product features or capabilities
- Process steps (when not using Checklist)
- Temporary home for pricing plans (until dedicated Pricing section is added)
- Any content that benefits from card-based presentation

CONTENT IDENTIFICATION PATTERNS:
Primary Triggers:
- "Features", "Services", "What We Offer", "Solutions", "Capabilities"
- "Our Services Include:", "We Provide:", "We Specialize In:"
- Bullet lists or numbered lists after an introduction
- Multiple items with heading + description pattern

Industry-Specific Patterns:
- Service Business: "Our Services", "What We Do", "Areas of Practice"
- Home Services: "Our Work Includes", "We Handle", "Services Offered"
- SaaS/Tech: "Key Features", "Platform Capabilities", "What's Included"
- E-commerce: "Product Features", "What You Get", "Included in Your Purchase"
- Medical/Legal: "Practice Areas", "Specializations", "Treatment Options"

List Pattern Recognition:
- Bullet points (•, -, *, ►) followed by feature/service names
- Numbered lists describing steps or phases
- Colon-separated lists (Feature: Description)
- Bold headings followed by explanations

FIELD MAPPING RULES:
- eyebrow_heading: Category labels like "Services", "Features", "What We Do"
- heading: Main section title, often question format ("How Can We Help?")
- content: Introduction paragraph explaining the offerings
- outro_content: Closing statement, additional context, or CTA text

CARDS ARRAY MAPPING:
For each list item or service, create a card object:
- heading: The feature/service name (keep concise, 2-5 words ideal)
- content: Description or benefit explanation (1-2 sentences)
- image: Leave empty (users add icons/images later)
- link/link_text: Only if document explicitly mentions "Learn more about [service]"

Card Creation Rules:
- Minimum 2 cards, maximum 12 cards (ideal: 3-6)
- If more than 12 items, prioritize the most important
- Maintain parallel structure in headings
- Keep descriptions roughly equal length

SPECIAL CONTENT ADAPTATIONS:
When document contains:
- Pricing tiers → Create cards for each plan with features as content
- Process steps → Number the card headings (1. Step One, 2. Step Two)
- Comparison points → Use cards to show advantages
- Team members → Map to cards (temporary until Team section added)
- Product categories → Each category becomes a card

VARIANT SELECTION LOGIC:
- theme_variant: Typically 'light' to contrast with Hero, alternate with previous
- layout_variant: 
  - Use 'content-left' for longer introductions
  - Use 'center' for balanced, symmetric presentations
- card_alignment: 
  - 'left' for text-heavy cards
  - 'center' for short, punchy content

QUALITY CHECKS:
- Ensure cards have consistent formatting (all have descriptions, similar length)
- Don't force unrelated content into Features just to fill space
- Verify heading isn't too similar to a Hero section
- Check that card headings are scannable and clear
- Avoid creating cards from paragraph content (need clear list structure)

EDGE CASE HANDLING:
- Single long list → Break into logical groups of 3-4 cards
- Mixed content types → Separate into multiple Features sections
- No clear list structure → Look for repeated patterns or categories
- Technical specifications → Can map to cards but keep descriptions simple
```

---

## 4. CHECKLIST SECTION

### Complete Field Structure

**Content Fields:**
- `eyebrow_heading` (string) - Subheading/tagline text above main heading
- `heading` (string) - Main headline/title (default: "Everything You Need")
- `content` (HTML string) - Introduction paragraphs wrapped in `<p>` tags
- `outro_content` (HTML string) - Optional closing paragraph(s)

**Media Fields:**
- `media_type` (enum) - Options: 'none', 'image', 'video'
- `featured_image` (URL string) - Image URL when media_type is 'image'
- `video_url` (URL string) - Video URL when media_type is 'video'

**Repeatable Elements:**
- `items` (array) - Array of checklist items, each containing:
  - `id`: Unique identifier (e.g., 'checklist_1')
  - `heading`: Item title/benefit name
  - `content`: Item description/explanation

- `global_blocks` (array) - Array of button objects (same as Hero)

**Variant Options:**
- `theme_variant` (enum) - Options: 'light', 'dark'
- `layout_variant` (enum) - Options: 'content-left', 'content-right', 'center'

**Legacy Fields (maintain but leave empty):**
- `primary_cta_label`, `primary_cta_url`, `secondary_cta_label`, `secondary_cta_url`

### AI Instructions for Checklist Section

```
The Checklist Section displays benefits, inclusions, or value propositions with a checkmark visual emphasis, ideal for "what's included" or "why choose us" content.

WHEN TO USE:
- "Why Choose Us", "What's Included", "What You Get" content
- Benefits lists with emphasis on completeness
- Value propositions that build trust
- Package inclusions or feature lists
- Advantages over competitors
- Guarantees or promises

CONTENT IDENTIFICATION PATTERNS:
Primary Triggers:
- "Why Choose Us", "Why [Company Name]", "The [Company] Advantage"
- "What's Included", "What You Get", "Everything You Need"
- "Our Promise", "We Guarantee", "You Can Count On"
- Checkmark symbols (✓, ✔, ☑, ✅) in the source document
- "Included:", "Benefits:", "You'll receive:", "Features include:"

Industry-Specific Patterns:
- Service Business: "Why hire us", "Our commitment", "What makes us different"
- Home Services: "What's included in every job", "Our guarantee", "You can expect"
- SaaS/Tech: "Everything you need to", "All plans include", "Out of the box"
- E-commerce: "Every purchase includes", "Free with every order", "What you'll love"
- Medical/Legal: "Our approach includes", "Every client receives", "Our commitment to you"

List Pattern Recognition:
- Checkmarks (✓) followed by benefit statements
- "All", "Every", "Complete", "Full" - words indicating comprehensiveness
- Positive affirmations ("Yes, we...", "We always...", "Guaranteed...")
- Value-focused language ("Free", "Included", "No extra charge", "Unlimited")
- Trust indicators ("Licensed", "Certified", "Insured", "Guaranteed")

FIELD MAPPING RULES:
- eyebrow_heading: Trust builders like "Why Choose Us", "Our Promise", "The Difference"
- heading: Main value proposition (e.g., "Everything You Need to Succeed")
- content: Introduction explaining the comprehensive nature of the offering
- outro_content: Reinforcement statement, guarantee details, or transition to next section

ITEMS ARRAY MAPPING:
For each benefit or inclusion, create an item object:
- heading: The benefit/feature name (2-5 words, action-oriented when possible)
- content: Brief explanation of the benefit (1-2 sentences max)
- id: Generate sequential IDs (checklist_1, checklist_2, etc.)

Item Creation Rules:
- Minimum 3 items, maximum 8 items (ideal: 4-6)
- Focus on benefits over features
- Keep headings parallel in structure
- Emphasize value and outcomes
- Order from most to least impactful

CONTENT TRANSFORMATION PATTERNS:
Transform these patterns into checklist items:
- "We offer X" → "X Included"
- "You get Y" → "Y Provided"
- "Includes Z" → "Z Built-In"
- "Free A" → "A at No Extra Cost"
- Feature statements → Benefit statements

DIFFERENTIATION FROM FEATURES:
Use Checklist when:
- Content emphasizes inclusion/completeness (✓ Everything included)
- Focus is on value/benefits rather than capabilities
- Building trust through promises/guarantees
- Comparing against competitors ("Unlike others, we...")

Use Features when:
- Content describes services or capabilities
- Each item needs detailed explanation
- Items are distinct offerings rather than inclusions
- Content is more educational than persuasive

VARIANT SELECTION LOGIC:
- theme_variant: Often 'light' to feel approachable and trustworthy
- layout_variant:
  - 'content-left' with media on right for visual balance
  - 'center' for impactful, focused presentations

QUALITY CHECKS:
- Ensure items feel like benefits, not just features
- Verify checkmark emphasis makes sense (these should feel like "wins")
- Don't use for negative comparisons or what's NOT included
- Keep items positive and value-focused
- Check that it doesn't duplicate Features section content

EDGE CASE HANDLING:
- Mix of benefits and features → Prioritize benefits for Checklist
- Very long benefit descriptions → Shorten to key points
- No clear benefits → Consider using Features section instead
- Numbered lists → Can work if they're benefit-focused
```

---

## 5. FAQ SECTION

### Complete Field Structure

**Content Fields:**
- `eyebrow_heading` (string) - Subheading/tagline text above main heading
- `heading` (string) - Main headline/title (default: "Frequently Asked Questions")
- `content` (HTML string) - Introduction paragraphs wrapped in `<p>` tags
- `outro_content` (HTML string) - Optional closing paragraph(s)

**Media Fields:**
- `media_type` (enum) - Options: 'none', 'image', 'video'
- `featured_image` (URL string) - Image URL when media_type is 'image'
- `video_url` (URL string) - Video URL when media_type is 'video'

**Repeatable Elements:**
- `faq_items` (array) - Array of Q&A pairs, each containing:
  - `id`: Unique identifier (e.g., 'faq_1')
  - `question`: The question text
  - `answer`: The answer wrapped in `<p>` tags

- `global_blocks` (array) - Array of button objects (same as Hero)

**Variant Options:**
- `theme_variant` (enum) - Options: 'light', 'dark'
- `layout_variant` (enum) - Options: 'content-left', 'content-right', 'center'

**Legacy Fields (maintain but leave empty):**
- `primary_cta_label`, `primary_cta_url`, `secondary_cta_label`, `secondary_cta_url`

### AI Instructions for FAQ Section

```
The FAQ Section addresses common questions and concerns, reducing support inquiries and building trust through transparent information.

WHEN TO USE:
- Explicit Q&A format with "Q:" or "Question:" markers
- Headings that are questions (contain "?", start with "How", "What", "Why", "Can", "Do")
- "FAQ", "Frequently Asked Questions", "Common Questions", "Q&A" headings
- Customer support content addressing concerns
- Troubleshooting or how-to content in Q&A format
- Pre-purchase questions and objection handling

CONTENT IDENTIFICATION PATTERNS:
Primary Triggers:
- Questions ending with "?" (most reliable indicator)
- Question starters: How, What, Why, When, Where, Can, Do, Does, Is, Are, Will, Should
- Phrases: "How to", "What if", "Can I", "Do you", "Is it possible"
- Section headers: "FAQ", "Questions", "Q&A", "Ask Us", "You Asked"

Question Format Recognition:
- "Q: [Question]" followed by "A: [Answer]"
- Bold/emphasized question followed by answer paragraph
- Numbered questions (1. How do I...?)
- Question as heading (H2/H3) with answer below
- Interview-style format with Q&A markers

INDUSTRY-SPECIFIC FAQ PATTERNS:

Service Businesses (Legal/Medical/Consulting):
- "How long does [service] take?"
- "What areas do you serve?"
- "Are you licensed and insured?"
- "How much does it cost?"
- "Do you offer free estimates/consultations?"
- "What should I expect during my first visit?"

Home Services:
- "Do you offer emergency service?"
- "Are you available on weekends?"
- "What payment methods do you accept?"
- "Do you provide warranties?"
- "How soon can you come out?"

SaaS/Technology:
- "Is there a free trial?"
- "What integrations are available?"
- "How secure is my data?"
- "Can I cancel anytime?"
- "What support is included?"
- "Does it work on mobile?"
- "How do I get started?"

E-commerce:
- "What is your return policy?"
- "How long does shipping take?"
- "Do you ship internationally?"
- "What payment methods do you accept?"
- "Is my payment information secure?"
- "Can I track my order?"
- "Do you offer bulk discounts?"

Non-profit:
- "How are donations used?"
- "Is my donation tax-deductible?"
- "How can I volunteer?"
- "Who do you help?"
- "How can I get involved?"
- "Where does the money go?"

FIELD MAPPING RULES:
- eyebrow_heading: "Have Questions?", "Need Help?", "Quick Answers", "FAQ"
- heading: Main title (e.g., "Frequently Asked Questions", "Common Questions About [Topic]")
- content: Introduction explaining purpose ("Find answers to common questions about our services.")
- outro_content: Support contact info, "Didn't find your answer?", additional help options

FAQ_ITEMS ARRAY MAPPING:
For each Q&A pair, create an faq_item object:
- question: The question text (keep the "?" at the end)
- answer: The answer wrapped in `<p>` tags
- id: Generate sequential IDs (faq_1, faq_2, etc.)

Question Extraction Rules:
- Remove "Q:", "Question:", or similar prefixes
- Keep the question mark at the end
- Maintain natural question phrasing
- Convert statements to questions if needed

Answer Formatting Rules:
- Wrap each answer paragraph in `<p>` tags
- Convert bullet points to paragraph form if concise
- Keep answers helpful and specific (1-3 paragraphs ideal)
- Include relevant details but avoid overwhelming

Ordering Guidelines:
- Most important/common questions first
- Group related questions together
- Purchase/pricing questions typically middle
- Contact/support questions often last

CONTENT TO EXCLUDE FROM FAQ:
- Testimonials (even if phrased as Q&A like "What do clients say?")
- Process steps (unless explicitly Q&A format)
- Feature descriptions (belongs in Features)
- Marketing content disguised as questions
- Rhetorical questions without real answers

STATEMENT-TO-QUESTION CONVERSION:
When content isn't in question format but addresses common concerns:
- "Pricing Information" → "How much does it cost?"
- "Refund Policy" → "What is your refund policy?"
- "Getting Started" → "How do I get started?"
- "Requirements" → "What are the requirements?"
- "Processing Time" → "How long does it take?"

MIXED CONTENT HANDLING:
If Q&A mixed with other content:
- Extract only clear Q&A pairs
- Put non-Q&A intro content in content field
- Put non-Q&A closing content in outro_content field
- Don't force non-question content into Q&A format

QUALITY CHECKS:
- Each item has both question and answer
- Questions end with "?"
- Answers are wrapped in `<p>` tags
- 4-8 FAQ items ideal (can have more if content warrants)
- Questions address real customer concerns
- Answers are helpful, specific, and complete
- No duplicate or overly similar questions
- Natural conversational tone in answers

VARIANT SELECTION LOGIC:
- theme_variant: Often 'light' for approachable, helpful feel
- layout_variant:
  - 'center' commonly used for focused Q&A presentation
  - 'content-left' if there's significant intro content
  - Consider accordion interaction in all layouts

EDGE CASE HANDLING:
- Technical documentation: Focus on user-facing questions, not implementation details
- Very long answers: Keep to 3 paragraphs max, link to detailed docs if needed
- Single question: Not ideal for FAQ section, consider incorporating elsewhere
- Questions without clear answers: Exclude or provide "Contact us for details"
- Pricing questions without prices: "Contact us for a customized quote"
```

---

## 6. STATS SECTION

### Complete Field Structure

**Content Fields:**
- `eyebrow_heading` (string) - Subheading/tagline text above main heading
- `heading` (string) - Main headline/title (default: "By the Numbers")
- `content` (HTML string) - Introduction paragraphs wrapped in `<p>` tags
- `outro_content` (HTML string) - Optional closing paragraph(s)

**Media Fields:**
- `media_type` (enum) - Options: 'none', 'image', 'video'
- `featured_image` (URL string) - Image URL when media_type is 'image'
- `video_url` (URL string) - Video URL when media_type is 'video'

**Repeatable Elements:**
- `stats` (array) - Array of statistics/metrics, each containing:
  - `id`: Unique identifier (e.g., 'stat_1')
  - `number`: The statistic value (e.g., "99%", "10,000+", "$5M")
  - `label`: Short label for the stat (e.g., "Customer Satisfaction")
  - `description`: Optional context/explanation (e.g., "Based on 1,000 reviews")

- `global_blocks` (array) - Array of button objects (same as Hero)

**Variant Options:**
- `theme_variant` (enum) - Options: 'light', 'dark'
- `layout_variant` (enum) - Options: 'content-left', 'content-right', 'center'

**Legacy Fields (maintain but leave empty):**
- `primary_cta_label`, `primary_cta_url`, `secondary_cta_label`, `secondary_cta_url`

### AI Instructions for Stats Section

```
The Stats Section displays key metrics, achievements, and numerical proof points to build credibility and demonstrate impact or success.

WHEN TO USE:
- Numbers with labels (percentages, counts, ratings, years)
- "By the numbers", "Our impact", "Results", "Achievements" headings
- Metrics that demonstrate success or scale
- Social proof through quantifiable data
- Company milestones or achievements
- Performance indicators or success metrics

CONTENT IDENTIFICATION PATTERNS:
Primary Triggers:
- Numbers prominently displayed (99%, 500+, #1, 10,000)
- "By the Numbers", "Our Impact", "Our Results", "The Numbers"
- "Stats", "Statistics", "Metrics", "Key Figures"
- Year founded, years in business, clients served
- Success rates, satisfaction scores, ratings
- Growth metrics, performance indicators

Number Format Recognition:
- Percentages: 99%, 100%, 95.5%
- Counts: 10,000+, 50M, 1,000, 500K
- Ratings: 4.9/5, 5 stars, A+ rating, #1
- Time: 10+ years, Since 2010, 24/7, 15 minutes
- Money: $5M saved, $1B processed, 50% savings
- Rankings: #1 rated, Top 10, Best in class

INDUSTRY-SPECIFIC STATS PATTERNS:

Service Businesses (Legal/Medical/Consulting):
- "95% Success Rate"
- "10,000+ Cases Handled"
- "30+ Years Experience"
- "24/7 Emergency Service"
- "5-Star Average Rating"
- "Licensed in 50 States"

Home Services:
- "1,000+ Homes Serviced"
- "Same Day Service"
- "100% Satisfaction Guarantee"
- "15+ Years in Business"
- "A+ BBB Rating"
- "500+ 5-Star Reviews"

SaaS/Technology:
- "99.9% Uptime"
- "50M+ Active Users"
- "120+ Countries"
- "500+ Integrations"
- "24/7 Support"
- "10x Faster Processing"
- "$10M+ Saved for Clients"

E-commerce:
- "1M+ Products Sold"
- "50,000+ Happy Customers"
- "4.8★ Average Rating"
- "30-Day Returns"
- "Free Shipping Over $50"
- "24-Hour Processing"

Non-profit:
- "100,000 Lives Impacted"
- "$5M Raised"
- "500+ Volunteers"
- "20 Years of Service"
- "95% Goes to Programs"
- "50 Communities Served"

FIELD MAPPING RULES:
- eyebrow_heading: "Our Impact", "The Proof", "Results That Matter", "Numbers Don't Lie"
- heading: Main title (e.g., "Trusted by Thousands", "Our Track Record")
- content: Introduction explaining what the numbers represent
- outro_content: Call-to-action or additional context about the metrics

STATS ARRAY MAPPING:
For each statistic, create a stat object:
- number: The numerical value (keep symbols like %, +, $, #)
- label: Short descriptive label (2-4 words ideal)
- description: Optional context (1 short sentence max)
- id: Generate sequential IDs (stat_1, stat_2, etc.)

Stat Extraction Rules:
- Keep number formatting as-is (99%, 10K, $5M)
- Include symbols and units with the number
- Extract the most impactful metrics (3-6 ideal)
- Ensure variety in metric types

Label Creation Guidelines:
- Keep labels short and clear (2-4 words)
- Use title case for consistency
- Focus on what the number represents
- Avoid redundant words

Description Guidelines:
- Optional - only if context is necessary
- Keep very brief (5-10 words)
- Provide timeframe or source if relevant
- Don't repeat information from label

STAT ORDERING LOGIC:
1. Most impressive/impactful first
2. Group related metrics together
3. Mix different types (%, counts, time)
4. End with forward-looking metrics if available

CONTENT TRANSFORMATION PATTERNS:
Transform these patterns into stats:
- "We have served over 10,000 clients" → Number: "10,000+", Label: "Clients Served"
- "Operating since 2010" → Number: "14+", Label: "Years of Experience"
- "Available 24 hours a day" → Number: "24/7", Label: "Support Available"
- "Rated 4.9 out of 5 stars" → Number: "4.9★", Label: "Average Rating"
- "Save up to 50%" → Number: "50%", Label: "Average Savings"

NUMBER FORMATTING CONVENTIONS:
- Large numbers: Use K, M, B (10K, 5M, 1B)
- Percentages: Include % symbol (99%)
- Ratings: Use ★ or /5 format (4.9★ or 4.9/5)
- Money: Include $ and abbreviate (e.g., $5M)
- Time: Be specific (24/7, 10+ years, Since 2010)
- Rankings: Include # or position (#1, Top 10)

QUALITY CHECKS:
- Stats are quantifiable and specific
- Numbers are impressive or meaningful
- Labels clearly explain what numbers represent
- Mix of different metric types
- 3-6 stats ideal (4 creates nice grid)
- Descriptions only when adding value
- No vague or unverifiable claims

VARIANT SELECTION LOGIC:
- theme_variant: Often 'dark' for impact, but alternate with previous
- layout_variant: 'center' commonly used for balanced presentation

EDGE CASE HANDLING:
- Text-heavy statistics: Extract just the key number and label
- Ranges: Use the most impressive end (e.g., "50-100 clients" → "100+")
- No clear numbers: Look for years, counts, percentages hidden in text
- Too many stats: Prioritize most impressive/relevant 4-6
- Vague numbers: Skip if not specific (e.g., "many clients" → exclude)
- Dated statistics: Include year in description if relevant

CONTENT TO EXCLUDE:
- Testimonial quotes (even with numbers)
- Feature lists (belongs in Features/Checklist)
- Process steps (unless they're metrics)
- Unquantifiable claims ("Best service")
- Future projections (unless clearly labeled)
```

---

## 7. TESTIMONIALS SECTION

### Complete Field Structure

**Content Fields:**
- `eyebrow_heading` (string) - Subheading/tagline text above main heading
- `heading` (string) - Main headline/title (default: "What Our Customers Say")
- `content` (HTML string) - Introduction paragraphs wrapped in `<p>` tags
- `outro_content` (HTML string) - Optional closing paragraph(s)

**Media Fields:**
- `media_type` (enum) - Options: 'none', 'image', 'video'
- `featured_image` (URL string) - Image URL when media_type is 'image'
- `video_url` (URL string) - Video URL when media_type is 'video'

**Repeatable Elements:**
- `testimonials` (array) - Array of testimonial/review items, each containing:
  - `id`: Unique identifier (e.g., 'testimonial_1')
  - `content`: The testimonial quote text
  - `author_name`: Name of the person giving testimonial
  - `author_title`: Title/company/role of the author
  - `author_image`: URL to author's photo (leave empty for user to add)
  - `rating`: Numeric rating 1-5 (default to 5 if not specified)

- `global_blocks` (array) - Array of button objects (same as Hero)

**Variant Options:**
- `theme_variant` (enum) - Options: 'light', 'dark'
- `layout_variant` (enum) - Options: 'content-left', 'content-right', 'center'

**Legacy Fields (maintain but leave empty):**
- `primary_cta_label`, `primary_cta_url`, `secondary_cta_label`, `secondary_cta_url`

### AI Instructions for Testimonials Section

```
The Testimonials Section displays customer reviews, client feedback, and social proof to build trust and credibility through real experiences.

WHEN TO USE:
- Quotation marks with customer/client attribution
- "What our clients say", "Reviews", "Testimonials", "Customer Feedback" headings
- Star ratings with review text
- Success stories or case study quotes
- Client praise or recommendation statements
- Attribution patterns like "- Name, Company" or "Name | Title"

CONTENT IDENTIFICATION PATTERNS:
Primary Triggers:
- Quotation marks (" " or " ") around text
- Attribution lines with dash, hyphen, or em-dash (-, –, —)
- Star ratings (★★★★★, 5/5 stars, 5-star)
- Review/testimonial keywords: "said", "says", "loved", "amazing", "excellent", "recommend"
- Section headers: "Testimonials", "Reviews", "What Clients Say", "Success Stories", "Praise"

Quote Format Recognition:
- "Quote text" - Name, Title
- "Quote text" — Name | Company
- Quote text... - Name (Title)
- ★★★★★ "Review text" - Customer Name
- [Name]: "Quote text"
- Name, Company: "Quote text"

INDUSTRY-SPECIFIC TESTIMONIAL PATTERNS:

Service Businesses (Legal/Medical/Consulting):
- "They handled my case with professionalism..."
- "The best [service] I've ever received..."
- "I highly recommend [company] for..."
- "Professional, responsive, and effective..."
- "[Name] and the team were incredible..."

Home Services:
- "Quick, reliable, and affordable..."
- "They fixed our [problem] same day..."
- "Clean, professional work..."
- "Would definitely use again..."
- "Best [service] company in [city]..."

SaaS/Technology:
- "This tool transformed our workflow..."
- "ROI increased by X% after implementing..."
- "The support team is amazing..."
- "Easy to use and powerful..."
- "Saved us hours every week..."

E-commerce:
- "Great quality, fast shipping..."
- "Exactly as described..."
- "Love this product..."
- "Exceeded my expectations..."
- "Will definitely order again..."

Non-profit:
- "Their work changed my life..."
- "Grateful for their support..."
- "Making a real difference..."
- "Proud to support this cause..."
- "The impact has been incredible..."

FIELD MAPPING RULES:
- eyebrow_heading: "Testimonials", "Client Success", "Reviews", "What People Say"
- heading: Main title (e.g., "Trusted by Thousands", "Client Success Stories")
- content: Introduction about testimonials/social proof
- outro_content: CTA to read more reviews or become a client

TESTIMONIALS ARRAY MAPPING:
For each testimonial, create a testimonial object:
- content: The quote text (without quotation marks)
- author_name: Person's name
- author_title: Title, company, role, or location
- author_image: Leave empty (users add photos later)
- rating: 1-5 (default to 5 if not specified)
- id: Generate sequential IDs (testimonial_1, testimonial_2, etc.)

Quote Extraction Rules:
- Remove surrounding quotation marks
- Keep the testimonial text exactly as written
- Don't paraphrase or clean up grammar
- Preserve enthusiasm and authenticity

Attribution Parsing:
- Extract name from attribution line
- Separate title/company if provided
- Handle various formats (dash, pipe, comma)
- Use "Customer" if no name provided
- Include location if mentioned

Rating Assignment:
- Look for explicit star ratings (★★★★★ = 5)
- "5 stars" or "5/5" = rating: 5
- Very positive with no rating = default to 5
- Neutral/constructive = consider 4
- If no clear sentiment = default to 5

TESTIMONIAL ORDERING LOGIC:
1. Most impactful/detailed first
2. Mix different types (product, service, support)
3. Include variety of industries/roles if applicable
4. End with aspirational/transformation stories

CONTENT TRANSFORMATION PATTERNS:
Transform these patterns:
- "John from ABC Corp loved our service" → Extract as testimonial from John
- Case study with quotes → Extract just the quotes
- Success story narrative → Pull out direct quotes only
- Review with pros/cons → Focus on positive statements

QUALITY CHECKS:
- Each testimonial has attribution (name at minimum)
- Content is a direct quote or review
- No duplicate testimonials
- 3-5 testimonials ideal (can have more)
- Mix of lengths (some short, some detailed)
- Authentic voice preserved
- Specific benefits or results mentioned when possible

VARIANT SELECTION LOGIC:
- theme_variant: Often 'light' for trust/approachability
- layout_variant: 'center' commonly used for testimonials

EDGE CASE HANDLING:
- Anonymous reviews: Use "Valued Customer" or "Anonymous"
- Very long testimonials: Keep full quote if impactful, don't truncate
- Mixed review (pros/cons): Extract positive portions only
- Interview format: Extract answers as testimonials
- Case study format: Pull direct quotes, not narrative
- No clear attribution: Look for context clues or use generic

CONTENT TO EXCLUDE:
- Your own marketing copy about testimonials
- Statistical claims not in quotes ("90% of clients...")
- Feature descriptions disguised as testimonials
- Hypothetical or example testimonials
- Questions from customers (belongs in FAQ)
- Press mentions (unless they're quotes)

SPECIAL CONSIDERATIONS:
- B2B testimonials often include company names and titles
- B2C testimonials may just have first name and location
- Medical/legal may use initials for privacy
- E-commerce often includes product names
- Non-profits focus on impact stories
```

---

## Visual Flow Rules (Apply to All Sections)

### Theme Alternation
- Alternate between 'light' and 'dark' theme_variant to create visual rhythm
- Avoid consecutive sections with the same theme_variant
- First section typically starts with 'dark' for impact

### Layout Variation
- Mix layout_variant options to avoid monotony
- Use 'center' layout sparingly for emphasis
- Consider content length when choosing layout

### Section Sequencing
- Avoid placing similar section types consecutively (e.g., two Features sections)
- Create logical content flow based on document structure
- Respect the document's original organization

---

## General AI Processing Rules

1. **Preserve Original Content**: Never paraphrase or modify the original text
2. **Use Exact Wording**: Copy content exactly as it appears in the document
3. **HTML Formatting**: Wrap all content paragraphs in `<p>` tags
4. **Empty Fields**: Leave media fields and CTA fields empty unless explicitly provided
5. **Create Sufficient Sections**: Generate as many sections as needed to include ALL document content
6. **Field Validation**: Ensure all required fields are populated, even if with default values

---

*Last Updated: [Current Date]*
*Version: 1.0.0*