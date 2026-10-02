<?php

/**
 * @file
 * Arabic content translations: menu links, site config, taxonomy terms and the
 * seeded product / news / project nodes. UI strings are handled separately via
 * the imported albaron.ar.po. Idempotent.
 *
 * Run with: ddev drush php:script scripts/setup_arabic.php
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;

$ctm = \Drupal::service('content_translation.manager');

// ---------------------------------------------------------------------------
// 0. Make the relevant fields translatable, then refresh definitions.
// ---------------------------------------------------------------------------
$ctm->setEnabled('menu_link_content', 'menu_link_content', TRUE);

$translatable = [
  'node.product.field_summary', 'node.product.field_dimensions', 'node.product.field_thickness', 'node.product.body',
  'node.news.field_summary', 'node.news.body',
  'node.project.field_summary', 'node.project.field_location', 'node.project.body',
];
foreach ($translatable as $id) {
  [$entity, $bundle, $field] = explode('.', $id);
  $fc = FieldConfig::loadByName($entity, $bundle, $field);
  if ($fc && !$fc->isTranslatable()) {
    $fc->setTranslatable(TRUE)->save();
    print "Field translatable: $id\n";
  }
}

// Language-neutral fields must be SHARED (non-translatable) so Arabic inherits
// images, taxonomy references, stone links, codes and the year unchanged.
$shared = [
  'node.product.field_product_code', 'node.product.field_stone_family', 'node.product.field_color',
  'node.product.field_finish', 'node.product.field_application', 'node.product.field_images', 'node.product.field_featured',
  'node.news.field_images',
  'node.project.field_images', 'node.project.field_stone_used', 'node.project.field_year',
];
foreach ($shared as $id) {
  [$entity, $bundle, $field] = explode('.', $id);
  $fc = FieldConfig::loadByName($entity, $bundle, $field);
  if ($fc && $fc->isTranslatable()) {
    $fc->setTranslatable(FALSE)->save();
    print "Field shared (non-translatable): $id\n";
  }
}
\Drupal::entityTypeManager()->clearCachedDefinitions();
\Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

/**
 * Add or update an Arabic translation on a content entity.
 */
$translate = static function ($entity, array $fields): void {
  if (!$entity) {
    return;
  }
  $t = $entity->hasTranslation('ar') ? $entity->getTranslation('ar') : $entity->addTranslation('ar');
  foreach ($fields as $name => $value) {
    if ($entity->hasField($name)) {
      $t->set($name, $value);
    }
  }
  $t->save();
};

// ---------------------------------------------------------------------------
// 1. Site name + slogan (config language override).
// ---------------------------------------------------------------------------
\Drupal::languageManager()->getLanguageConfigOverride('ar', 'system.site')
  ->set('name', 'البارون')
  ->set('slogan', 'حجر ورخام طبيعي')
  ->save();
print "Site config (ar) overridden.\n";

// ---------------------------------------------------------------------------
// 2. Main menu links.
// ---------------------------------------------------------------------------
$menu_titles = [
  'Home' => 'الرئيسية',
  'News' => 'الأخبار',
  'Products' => 'المنتجات',
  'Gallery' => 'المعرض',
  'Projects' => 'المشاريع',
  'Company' => 'الشركة',
  'Capabilities' => 'الإمكانات',
  'Contact' => 'اتصل بنا',
];
$links = \Drupal::entityTypeManager()->getStorage('menu_link_content')
  ->loadByProperties(['menu_name' => 'main']);
foreach ($links as $link) {
  $en = $link->getTitle();
  if (isset($menu_titles[$en])) {
    $translate($link, ['title' => $menu_titles[$en]]);
    print "Menu link translated: $en\n";
  }
}

// ---------------------------------------------------------------------------
// 3. Taxonomy terms.
// ---------------------------------------------------------------------------
$term_names = [
  // stone_family
  'Limestone' => 'حجر جيري', 'Marble' => 'رخام', 'Travertine' => 'ترافرتين',
  'Sandstone' => 'حجر رملي', 'Breccia' => 'بريشيا',
  // color
  'Beige' => 'بيج', 'Cream' => 'كريمي', 'Golden' => 'ذهبي', 'Grey' => 'رمادي',
  'Rosso / Red' => 'أحمر', 'White' => 'أبيض',
  // finish
  'Polished' => 'مصقول', 'Honed' => 'مجلوّ', 'Brushed' => 'مفرشى',
  'Bush-hammered' => 'مطروق', 'Split-face' => 'مشقوق الوجه',
  'Sandblasted' => 'مصنفر', 'Tumbled' => 'مُدحرج',
  // application
  'Wall Cladding' => 'تكسية الجدران', 'Flooring' => 'أرضيات', 'Facades' => 'واجهات',
  'Countertops' => 'أسطح العمل', 'Landscaping' => 'تنسيق المواقع', 'Stairs & Steps' => 'سلالم ودرجات',
];
$term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($term_names as $en => $ar) {
  foreach ($term_storage->loadByProperties(['name' => $en]) as $term) {
    $translate($term, ['name' => $ar]);
  }
}
print "Taxonomy terms translated.\n";

// ---------------------------------------------------------------------------
// 4. Product nodes.
// ---------------------------------------------------------------------------
$body_tpl = static fn(string $summary, string $title): array => [
  'value' => '<p>' . $summary . ' يوفّر البارون ' . $title . ' بتشطيبات وأبعاد معايَرة، مُغلَّفاً وفق معيار التصدير لتوصيل موثوق إلى المشاريع الدولية.</p>',
  'format' => 'basic_html',
];

$products = [
  'Jerusalem Gold Limestone' => ['حجر القدس الذهبي الجيري', 'الحجر الجيري الذهبي الدافئ الأيقوني للأرض المقدسة — كثيفٌ ومقاوم للعوامل الجوية وفلسطينيٌّ بلا منازع.', '600×300، 400×400، أطوال حرة', '2–5 سم'],
  'Bethlehem Cream' => ['كريم بيت لحم', 'حجرٌ جيري كريمي ناعمٌ ومتجانس، مثاليٌّ للأرضيات الداخلية الأنيقة والواجهات الراقية.', '600×600، 800×400', '2–3 سم'],
  'Hebron Beige' => ['بيج الخليل', 'حجرٌ بيج متين بملمسٍ غني، مثاليٌّ للتكسية الخارجية وتنسيق المواقع.', '400×200، دبش عشوائي', '3–6 سم'],
  'Galilee Grey Marble' => ['رخام الجليل الرمادي', 'رخامٌ رمادي بارد بتعريقٍ خفيف للمساحات الداخلية والأسطح العصرية.', '600×600، ألواح حتى 2.6 م', '2–3 سم'],
  'Nablus Rose' => ['وردي نابلس', 'بريشيا وردية دافئة بحركةٍ درامية — حجرٌ مميّز للأسطح البارزة.', 'ألواح، قص حسب المقاس', '2–3 سم'],
  'Royal Botticino' => ['بوتيتشينو الملكي', 'رخامٌ فاتح كلاسيكي بتعريقٍ ناعم، متعدّد الاستخدامات للأرضيات والسلالم.', '600×600، درجات حسب المواصفات', '2–4 سم'],
  'Desert Travertine' => ['ترافرتين الصحراء', 'ترافرتين بملمسٍ طبيعي يضفي دفئاً عضوياً على المسابح والأفنية والجدران.', '610×406، بلاط أرضيات', '3 سم'],
  'Ramallah White' => ['أبيض رام الله', 'حجرٌ جيري ناصع ونظيف يرفع مستوى الواجهات بدرجةٍ لونية عصرية مضيئة.', '600×300، ألواح', '2–4 سم'],
  'Olive Breccia' => ['بريشيا الزيتون', 'بريشيا ترابية بطابعٍ متعدّد الطبقات، متناغمة داخلياً وخارجياً.', 'ألواح، قص حسب المقاس', '2–3 سم'],
];
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
foreach ($products as $en => $def) {
  [$title_ar, $summary_ar, $dims_ar, $thick_ar] = $def;
  foreach ($node_storage->loadByProperties(['type' => 'product', 'title' => $en]) as $node) {
    $translate($node, [
      'title' => $title_ar,
      'field_summary' => $summary_ar,
      'field_dimensions' => $dims_ar,
      'field_thickness' => $thick_ar,
      'body' => $body_tpl($summary_ar, $title_ar),
    ]);
    print "Product translated: $en\n";
  }
}

// ---------------------------------------------------------------------------
// 5. News nodes.
// ---------------------------------------------------------------------------
$news = [
  'Al-Baron Exhibits at Marmomac 2026 in Verona' => [
    'البارون يشارك في معرض مارموماك 2026 في فيرونا',
    'التقِ بفريقنا في أبرز معرض عالمي للحجر الطبيعي واكتشف أحدث تشطيبات الحجر الجيري والرخام الفلسطيني لدينا.',
    '<p>يفخر البارون بالعودة إلى معرض <strong>مارموماك</strong> في فيرونا بإيطاليا — الحدث المرجعي العالمي للحجر الطبيعي. سيرى الزوار مجموعتنا الكاملة للتصدير من حجر القدس الذهبي الجيري وكريم بيت لحم ورخام الجليل الرمادي، إلى جانب تشطيبات معايَرة جديدة طُوّرت لمشاريع الواجهات والأرضيات الدولية.</p><p>سيكون فريقنا حاضراً لمناقشة المواصفات والقياسات المخصّصة والخدمات اللوجستية للتصدير للمشترين في أوروبا والخليج وأمريكا الشمالية.</p>',
  ],
  'New Brushed & Sandblasted Finishes Added to the Export Range' => [
    'إضافة تشطيبات مفرشاة ومصنفرة جديدة إلى مجموعة التصدير',
    'وسّعنا طاقتنا في تشطيب الأسطح بتشطيبات جديدة ذات ملمس للتكسية الخارجية وتنسيق المواقع.',
    '<p>استجابةً للطلب المتزايد من المعماريين، أنشأ البارون خطوط تشطيب إضافية في منشأته في بيت لحم. تضيف التوسعة ملامس <strong>مفرشاة</strong> و<strong>مصنفرة</strong> متناسقة عبر مجموعتنا من الحجر الجيري، مثالية للأرضيات عالية الحركة والواجهات وتنسيق المواقع.</p><p>تُحفظ جميع التشطيبات الجديدة ضمن تفاوتات اللون والأبعاد الموثّقة لدينا، وهي متاحة لأخذ العينات عند الطلب.</p>',
  ],
  'EU4Trade Partnership Powers Al-Baron’s Global Expansion' => [
    'شراكة EU4Trade تدعم توسّع البارون العالمي',
    'بدعمٍ من بال تريد والاتحاد الأوروبي، يعزّز البارون حضوره الرقمي وجاهزيته للتصدير.',
    '<p>من خلال برنامج <strong>EU4Trade</strong> وبالشراكة مع بال تريد، استثمر البارون في منصّة رقمية جديدة ثنائية اللغة وكتالوج ووثائق جودة لخدمة المشترين الدوليين بشكل أفضل.</p><p>تعزّز هذه المبادرة التزامنا بتوريد موثوق وبمعايير التصدير للحجر الفلسطيني الطبيعي الفاخر للمشاريع حول العالم.</p>',
  ],
];
foreach ($news as $en => $def) {
  [$title_ar, $summary_ar, $body_ar] = $def;
  foreach ($node_storage->loadByProperties(['type' => 'news', 'title' => $en]) as $node) {
    $translate($node, [
      'title' => $title_ar,
      'field_summary' => $summary_ar,
      'body' => ['value' => $body_ar, 'format' => 'basic_html'],
    ]);
    print "News translated: $en\n";
  }
}

// ---------------------------------------------------------------------------
// 6. Project nodes.
// ---------------------------------------------------------------------------
require __DIR__ . '/setup_palestinian_projects.php';

drupal_flush_all_caches();
print "== Arabic content translation complete ==\n";
