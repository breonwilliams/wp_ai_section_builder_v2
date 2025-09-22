# Component Mapping - jQuery to React

## Overview

This document maps current jQuery functions to React components, showing exactly how to convert the monolithic editor.js into modular React components.

## Core Editor Components

### Main Editor (editor.js lines 94-190 → Editor.jsx)

**Current jQuery:**
```javascript
function initEditor() {
    loadExistingSections();
    initSortable();
    bindEvents();
    // ... 100 lines of initialization
}
```

**React Component:**
```javascript
// components/Editor/Editor.jsx
const Editor = () => {
  const { sections, loadSections } = useEditorStore();
  
  useEffect(() => {
    loadSections(postId);
  }, []);

  return (
    <EditorProvider>
      <div className="aisb-editor">
        <Toolbar />
        <div className="aisb-editor-layout">
          <LeftSidebar />
          <Canvas sections={sections} />
          <RightSidebar />
        </div>
      </div>
    </EditorProvider>
  );
};
```

## Section Components Mapping

### Hero Section (lines 1194-1266 → Hero/)

**Current Form Generator:**
```javascript
function generateHeroForm(content) {
    var formHtml = '<div class="aisb-form-group">' +
        '<label>Heading *</label>' +
        '<input type="text" name="heading" value="' + content.heading + '">' +
        // ... 70+ lines of HTML string
    '</div>';
    return formHtml;
}
```

**React Components:**
```javascript
// components/Sections/Hero/HeroForm.jsx
const HeroForm = ({ content, onChange }) => {
  return (
    <Form>
      <FormGroup>
        <Label required>Heading</Label>
        <Input
          value={content.heading}
          onChange={(e) => onChange('heading', e.target.value)}
          maxLength={200}
        />
      </FormGroup>
      
      <FormGroup>
        <Label>Body Content</Label>
        <RichTextEditor
          value={content.content}
          onChange={(value) => onChange('content', value)}
        />
      </FormGroup>
      
      <MediaPicker
        value={content.featured_image}
        onChange={(media) => onChange('featured_image', media)}
      />
      
      <ThemeVariantSelector
        value={content.theme_variant}
        onChange={(variant) => onChange('theme_variant', variant)}
      />
    </Form>
  );
};

// components/Sections/Hero/HeroPreview.jsx
const HeroPreview = ({ content }) => {
  return (
    <section className={cn(
      'aisb-hero',
      `aisb-section--${content.theme_variant}`,
      `aisb-section--${content.layout_variant}`
    )}>
      <div className="aisb-hero__container">
        {content.eyebrow_heading && (
          <div className="aisb-hero__eyebrow">
            {content.eyebrow_heading}
          </div>
        )}
        <h1 className="aisb-hero__heading">
          {content.heading || 'Your Headline Here'}
        </h1>
        <div 
          className="aisb-hero__body"
          dangerouslySetInnerHTML={{ 
            __html: DOMPurify.sanitize(content.content) 
          }}
        />
        <ButtonGroup buttons={content.global_blocks} />
        <MediaDisplay media={content} />
      </div>
    </section>
  );
};
```

## Common Components Mapping

### Repeater Fields (lines 2054-2290 → RepeaterField/)

**Current jQuery:**
```javascript
function initCardsRepeater(content) {
    $(document).on('click', '.add-card-btn', function() {
        var newCard = '<div class="card-item">...</div>';
        $('#cards-container').append(newCard);
    });
}
```

**React Component:**
```javascript
// components/Common/RepeaterField/index.jsx
const RepeaterField = ({ 
  items, 
  onUpdate, 
  renderItem, 
  createNew,
  max = 20 
}) => {
  const handleAdd = () => {
    if (items.length < max) {
      onUpdate([...items, createNew()]);
    }
  };

  const handleRemove = (index) => {
    onUpdate(items.filter((_, i) => i !== index));
  };

  const handleReorder = (fromIndex, toIndex) => {
    const newItems = arrayMove(items, fromIndex, toIndex);
    onUpdate(newItems);
  };

  return (
    <DndContext onDragEnd={handleReorder}>
      <SortableContext items={items}>
        {items.map((item, index) => (
          <RepeaterItem
            key={item.id}
            index={index}
            onRemove={() => handleRemove(index)}
          >
            {renderItem(item, index)}
          </RepeaterItem>
        ))}
      </SortableContext>
      
      {items.length < max && (
        <Button onClick={handleAdd} variant="secondary">
          Add Item
        </Button>
      )}
    </DndContext>
  );
};
```

### Media Picker (lines 3365-3396 → MediaPicker/)

**Current jQuery:**
```javascript
$(document).on('click', '.aisb-media-upload', function() {
    var frame = wp.media({
        title: 'Select Image',
        button: { text: 'Use Image' },
        multiple: false
    });
    frame.on('select', function() {
        var attachment = frame.state().get('selection').first();
        $('#featured_image').val(attachment.url);
    });
    frame.open();
});
```

**React Component:**
```javascript
// components/Common/MediaPicker/index.jsx
const MediaPicker = ({ value, onChange, type = 'image' }) => {
  const openMediaLibrary = () => {
    const frame = wp.media({
      title: 'Select Media',
      button: { text: 'Use This Media' },
      multiple: false,
      library: { type }
    });

    frame.on('select', () => {
      const attachment = frame.state()
        .get('selection')
        .first()
        .toJSON();
      onChange(attachment.url);
    });

    frame.open();
  };

  return (
    <div className="aisb-media-picker">
      {value ? (
        <div className="aisb-media-preview">
          <img src={value} alt="Selected media" />
          <Button 
            onClick={() => onChange('')} 
            variant="danger"
            size="sm"
          >
            Remove
          </Button>
        </div>
      ) : (
        <Button onClick={openMediaLibrary} variant="secondary">
          Select {type}
        </Button>
      )}
    </div>
  );
};
```

## Event Handling Mapping

### jQuery Events → React Handlers

**Current jQuery (scattered throughout):**
```javascript
$(document).on('click', '.aisb-section-type', function() {
    var type = $(this).data('type');
    addSection(type);
});

$(document).on('input', '#heading', function() {
    updatePreview();
});
```

**React Event Handlers:**
```javascript
// Centralized in components
const SectionLibrary = () => {
  const addSection = useEditorStore(state => state.addSection);
  
  const handleSectionClick = (type) => {
    addSection(type);
  };

  return (
    <div className="section-library">
      {SECTION_TYPES.map(type => (
        <button
          key={type}
          onClick={() => handleSectionClick(type)}
          className="section-type-button"
        >
          {type.label}
        </button>
      ))}
    </div>
  );
};
```

## State Management Mapping

### Global State → Zustand Store

**Current jQuery:**
```javascript
var editorState = {
    sections: [],
    currentSection: null,
    isDirty: false
};

function updateSection(index, data) {
    editorState.sections[index] = data;
    editorState.isDirty = true;
    updatePreview();
}
```

**React/Zustand:**
```javascript
// stores/useEditorStore.js
const useEditorStore = create((set) => ({
  sections: [],
  currentSection: null,
  isDirty: false,

  updateSection: (index, data) => set((state) => ({
    sections: state.sections.map((s, i) => 
      i === index ? { ...s, ...data } : s
    ),
    isDirty: true
  })),

  // Derived state
  getSectionByIndex: (index) => (state) => 
    state.sections[index]
}));

// Usage in component
const MyComponent = () => {
  const sections = useEditorStore(state => state.sections);
  const updateSection = useEditorStore(state => state.updateSection);
  
  // Component logic
};
```

## AJAX → API Calls

### Save Sections (lines 4985-5057 → api/sections.js)

**Current jQuery:**
```javascript
function saveSections() {
    $.ajax({
        url: aisb_editor.ajax_url,
        type: 'POST',
        data: {
            action: 'aisb_save_sections',
            nonce: aisb_editor.nonce,
            post_id: $('#aisb-post-id').val(),
            sections: JSON.stringify(editorState.sections)
        },
        success: function(response) {
            showNotification('Saved successfully', 'success');
        }
    });
}
```

**React/Axios:**
```javascript
// api/sections.js
export const saveSections = async (postId, sections) => {
  try {
    const { data } = await axios.post(
      '/wp-json/aisb/v1/sections',
      {
        post_id: postId,
        sections
      },
      {
        headers: {
          'X-WP-Nonce': wpApiSettings.nonce
        }
      }
    );
    return data;
  } catch (error) {
    throw new Error(error.response?.data?.message || 'Save failed');
  }
};

// Usage with React Query
const useSaveSections = () => {
  const { sections } = useEditorStore();
  
  return useMutation({
    mutationFn: () => saveSections(postId, sections),
    onSuccess: () => {
      toast.success('Saved successfully');
      useEditorStore.setState({ isDirty: false });
    },
    onError: (error) => {
      toast.error(error.message);
    }
  });
};
```

## Utility Functions Mapping

### DOM Manipulation → React State

**Current:**
```javascript
function updatePreview() {
    var html = renderSection(section);
    $('#preview').html(html);
}
```

**React:**
```javascript
// No DOM manipulation needed - React handles it
const Canvas = () => {
  const sections = useEditorStore(state => state.sections);
  
  return (
    <div className="aisb-canvas">
      {sections.map((section, index) => (
        <SectionRenderer
          key={section.id}
          section={section}
          index={index}
        />
      ))}
    </div>
  );
};
```

## Component Hierarchy

```
Editor
├── EditorProvider
├── Toolbar
│   ├── SaveButton
│   ├── ClearAllButton
│   └── ToggleSidebarsButton
├── LeftSidebar
│   ├── TabNav
│   ├── SectionLibrary
│   │   └── SectionTypeButton[]
│   ├── SectionEditForm
│   │   ├── HeroForm
│   │   ├── FeaturesForm
│   │   └── [Other Section Forms]
│   └── SettingsPanel
│       ├── ColorPicker[]
│       └── ResetButton
├── Canvas
│   ├── EmptyState
│   └── SectionRenderer[]
│       ├── HeroSection
│       ├── FeaturesSection
│       └── [Other Sections]
└── RightSidebar
    └── PageStructure
        └── SortableList
            └── SectionItem[]
```

## Migration Priority

### Phase 1: Core Components
1. Editor shell
2. State management
3. Layout components
4. Basic section rendering

### Phase 2: Section Components
1. Hero (simplest, good template)
2. Features (repeater field example)
3. FAQ (accordion behavior)
4. Stats, Testimonials, Checklist
5. HeroForm (most complex)

### Phase 3: Common Components
1. RichTextEditor
2. MediaPicker
3. RepeaterField
4. Button components
5. Form controls

### Phase 4: Advanced Features
1. Drag-and-drop
2. AI integration
3. Keyboard shortcuts
4. Auto-save

## Testing Strategy

Each component should have:
```javascript
// __tests__/HeroSection.test.jsx
describe('HeroSection', () => {
  it('renders with default props', () => {
    render(<HeroSection />);
    expect(screen.getByText('Your Headline Here')).toBeInTheDocument();
  });

  it('sanitizes dangerous HTML', () => {
    const content = { 
      content: '<script>alert("XSS")</script>Safe content' 
    };
    render(<HeroSection content={content} />);
    expect(screen.queryByText('alert')).not.toBeInTheDocument();
    expect(screen.getByText('Safe content')).toBeInTheDocument();
  });
});
```

---

This mapping provides a clear path from jQuery spaghetti to React components.