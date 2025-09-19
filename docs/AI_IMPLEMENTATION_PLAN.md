# AI Implementation Plan - AI Section Builder v2

## Current Status (January 2025)

### ✅ Completed
1. **Comprehensive Research** - Analyzed real-world content patterns across industries
2. **Pattern Library** - Created AI_CONTENT_PATTERN_LIBRARY.md with industry-specific patterns
3. **Section Documentation** - Fully documented all 7 sections with:
   - Complete field structures
   - Content identification patterns
   - Industry-specific examples
   - Mapping rules and quality checks
   - Edge case handling

### 📁 Documentation Created
- `AI_SECTION_MAPPING_INSTRUCTIONS.md` - Complete mapping instructions for all 7 sections
- `AI_CONTENT_PATTERN_LIBRARY.md` - Real-world content patterns and future recommendations

---

## Next Steps for Implementation

### Phase 1: Core AI Document Processor (Priority)

#### 1. Create Document Processor Class
**File:** `/includes/class-document-processor.php`

```php
namespace AISB;

class Document_Processor {
    private $openai_client;
    private $instructions;
    
    public function __construct() {
        $this->load_instructions();
        $this->init_openai();
    }
    
    public function process_document($text) {
        // Send text + instructions to OpenAI
        // Parse response into sections array
        // Validate and clean data
        // Return formatted sections
    }
}
```

#### 2. Restore AJAX Handler
**File:** `/includes/ajax/class-document-ajax.php`

Key functions needed:
- `handle_document_upload()` - Process Word doc upload
- `extract_text_from_word()` - Extract text content
- `process_with_ai()` - Send to Document Processor
- `validate_sections()` - Ensure data integrity

#### 3. OpenAI Integration Requirements

**API Instructions Structure:**
```json
{
  "role": "system",
  "content": "You are an AI that maps Word document content to website sections. [Include full instructions from AI_SECTION_MAPPING_INSTRUCTIONS.md]"
}
```

**Expected Response Format:**
```json
{
  "sections": [
    {
      "type": "hero",
      "content": {
        "eyebrow_heading": "",
        "heading": "",
        "content": "",
        // ... all fields per documentation
      }
    }
  ]
}
```

---

### Phase 2: Testing & Refinement

#### Test Cases by Industry
1. **Service Business** - Legal firm, medical practice, consulting
2. **Home Services** - Plumbing, HVAC, landscaping
3. **SaaS/Tech** - Software product, app, platform
4. **E-commerce** - Product descriptions, store content
5. **Non-profit** - Mission statements, program descriptions

#### Quality Metrics to Track
- Section type accuracy (correct section chosen)
- Field mapping accuracy (content in right fields)
- Theme/layout variant selection
- Repeatable element extraction (cards, items, etc.)
- Edge case handling

---

### Phase 3: UI Enhancement

#### Editor Improvements
1. **AI Processing Indicator** - Show progress during processing
2. **Section Preview** - Show mapped sections before insertion
3. **Manual Adjustments** - Allow tweaking AI results
4. **Error Handling** - Clear messages for issues

#### Settings Page Additions
- OpenAI API key configuration
- Model selection (GPT-3.5 vs GPT-4)
- Processing preferences
- Instruction customization

---

## Technical Implementation Details

### 1. Word Document Processing
```php
// Use PHPWord library (already in project)
$phpWord = \PhpOffice\PhpWord\IOFactory::load($file_path);
$text = '';
foreach ($phpWord->getSections() as $section) {
    // Extract text from each element
}
```

### 2. OpenAI Request Structure
```php
$response = $openai->chat()->create([
    'model' => 'gpt-4',
    'messages' => [
        ['role' => 'system', 'content' => $instructions],
        ['role' => 'user', 'content' => $document_text]
    ],
    'temperature' => 0.3, // Lower for more consistent output
    'response_format' => ['type' => 'json_object']
]);
```

### 3. Section Validation
```php
private function validate_section($section) {
    // Check required fields exist
    // Validate field types
    // Ensure HTML content has <p> tags
    // Validate enum values (theme_variant, layout_variant)
    // Check array structures for repeatable elements
}
```

---

## Error Handling Strategy

### Common Issues to Handle
1. **Empty or insufficient content** - Provide helpful message
2. **Malformed document** - Graceful degradation
3. **API errors** - Retry logic with exponential backoff
4. **Invalid JSON response** - Fallback to manual parsing
5. **Rate limits** - Queue system for bulk processing

### User Feedback Messages
- "Processing your document..." (during)
- "Successfully created X sections" (success)
- "Unable to process: [specific reason]" (error)
- "Some content couldn't be mapped" (partial)

---

## Future Enhancements (Post-MVP)

### Priority 1 - New Sections
Based on research, add these sections next:
1. **Pricing/Plans Section** - 3-tier pricing tables
2. **Process/How It Works** - Step-by-step timeline
3. **Team/About Section** - Team member cards

### Priority 2 - Advanced Features
1. **Content Enhancement** - Suggest improvements to mapped content
2. **Multi-language Support** - Process documents in other languages
3. **Style Learning** - Learn from user's editing patterns
4. **Bulk Processing** - Handle multiple documents at once

### Priority 3 - Integration
1. **Direct Google Docs Import** - Skip download step
2. **CMS Migration Tool** - Import from other platforms
3. **Content Library** - Save and reuse section templates
4. **Version History** - Track changes to sections

---

## Development Checklist

### Immediate Tasks
- [ ] Set up OpenAI PHP client library
- [ ] Create Document_Processor class
- [ ] Implement AJAX handler
- [ ] Add API key settings field
- [ ] Test with sample documents
- [ ] Add error handling
- [ ] Create user documentation

### Testing Requirements
- [ ] Unit tests for Document_Processor
- [ ] Integration tests for AJAX flow
- [ ] Manual testing with real documents
- [ ] Cross-browser testing
- [ ] Performance testing with large documents

### Documentation Needs
- [ ] User guide for AI features
- [ ] API configuration instructions
- [ ] Troubleshooting guide
- [ ] Video tutorial

---

## Success Criteria

The AI implementation will be considered successful when:
1. ✅ 80%+ accuracy in section type selection
2. ✅ Correct field mapping for standard content patterns
3. ✅ Handles all 7 section types reliably
4. ✅ Processes documents in < 10 seconds
5. ✅ Clear error messages for edge cases
6. ✅ Users can refine AI output easily

---

## Risk Mitigation

### Technical Risks
- **API Dependency** - Add fallback for API outages
- **Cost Management** - Implement usage limits
- **Response Quality** - Allow manual override
- **Performance** - Add caching layer

### User Experience Risks
- **Expectations** - Clear messaging about AI capabilities
- **Learning Curve** - Comprehensive help documentation
- **Trust** - Always allow preview before applying

---

*Last Updated: January 2025*
*Status: Ready for Development*
*Next Action: Begin Phase 1 implementation*