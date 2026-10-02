<?php

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;

$project_examples = [
  [
    'legacy_title' => 'Boutique Hotel Facade — Dubai, UAE',
    'image' => 'news-bg.webp',
    'stones' => ['Jerusalem Gold Limestone', 'Ramallah White'],
    'en' => [
      'title' => 'Guesthouse Courtyard - Bethlehem',
      'location' => 'Bethlehem, Palestine',
      'summary' => 'An illustrative guesthouse concept with warm limestone entrance walls, framed windows and a shaded courtyard in Bethlehem.',
      'body' => '<p>This is an illustrative project concept, not a documented Al-Baron commission. Images are material and design references, not photographs of a verified completed site.</p><p>The proposal pairs Jerusalem Gold limestone wall cladding with Ramallah White window surrounds and entrance details. A sheltered courtyard provides a transition between the street and guest reception, taking inspiration from Bethlehem\'s stone architecture.</p><p>Stone samples would be reviewed together in daylight before selecting the final colour range. Panel layout, corner details and fixing methods would be coordinated with the project architect and installer.</p>',
      'alt' => 'Illustrative stone guesthouse facade reference',
    ],
    'ar' => [
      'title' => 'ساحة بيت ضيافة - بيت لحم',
      'location' => 'بيت لحم، فلسطين',
      'summary' => 'تصور توضيحي لبيت ضيافة في بيت لحم، بمدخل من الحجر الجيري الدافئ وإطارات حجرية للنوافذ وساحة مظللة.',
      'body' => '<p>هذا تصور توضيحي لمشروع، وليس عملا منفذا وموثقا لشركة البارون. الصور مراجع للخامات والتصميم، وليست صورا لموقع مكتمل تم التحقق منه.</p><p>يجمع المقترح بين تكسية الجدران بحجر القدس الذهبي وإطارات النوافذ وتفاصيل المدخل من حجر رام الله الأبيض. وتشكل الساحة المظللة مساحة انتقالية بين الشارع واستقبال الضيوف، مستلهمة من العمارة الحجرية في بيت لحم.</p><p>تراجع عينات الحجر معا في ضوء النهار قبل اعتماد التدرجات اللونية، وتنسق توزيعات الألواح وتفاصيل الزوايا وطرق التثبيت مع مهندس المشروع وفريق التركيب.</p>',
      'alt' => 'مرجع توضيحي لواجهة بيت ضيافة حجرية',
    ],
  ],
  [
    'legacy_title' => 'Private Villa Interiors — Amman, Jordan',
    'image' => 'products-bg.webp',
    'stones' => ['Bethlehem Cream', 'Royal Botticino'],
    'en' => [
      'title' => 'Family Home - Ramallah',
      'location' => 'Ramallah, Palestine',
      'summary' => 'An illustrative family-home concept for Ramallah, combining a stone entrance with cream-toned interior floors and carefully detailed stairs.',
      'body' => '<p>This is an illustrative project concept, not a documented Al-Baron commission. Images are material and design references, not photographs of a verified completed site.</p><p>The interior proposal combines Bethlehem Cream flooring with Royal Botticino stair treads and landings. Consistent floor levels, aligned joints and restrained edge details connect the entrance, living spaces and staircase.</p><p>Surface finishes would be selected for the intended use of each space, with stair-edge visibility, cleaning and maintenance considered before approval. Final sizes would follow a site survey rather than standard assumptions.</p>',
      'alt' => 'Illustrative natural stone residential entrance reference',
    ],
    'ar' => [
      'title' => 'منزل عائلي - رام الله',
      'location' => 'رام الله، فلسطين',
      'summary' => 'تصور توضيحي لمنزل عائلي في رام الله، يجمع مدخلا حجريا وأرضيات داخلية بدرجات كريمية وسلالم بتفاصيل مدروسة.',
      'body' => '<p>هذا تصور توضيحي لمشروع، وليس عملا منفذا وموثقا لشركة البارون. الصور مراجع للخامات والتصميم، وليست صورا لموقع مكتمل تم التحقق منه.</p><p>يجمع المقترح الداخلي بين أرضيات كريم بيت لحم ودرجات وبسطات سلالم من بوتيتشينو الملكي. وتربط مناسيب الأرضيات المتناسقة والفواصل المنتظمة وتفاصيل الحواف البسيطة بين المدخل ومساحات المعيشة والدرج.</p><p>تختار التشطيبات بحسب استخدام كل مساحة، مع مراعاة وضوح حواف الدرج والتنظيف والصيانة قبل الاعتماد. وتحدد المقاسات النهائية بعد الرفع الميداني للموقع.</p>',
      'alt' => 'مرجع توضيحي لمدخل منزل من الحجر الطبيعي',
    ],
  ],
  [
    'legacy_title' => 'Corporate Headquarters Lobby — Doha, Qatar',
    'image' => 'company-bg.webp',
    'stones' => ['Galilee Grey Marble'],
    'en' => [
      'title' => 'Commercial Building Entrance - Hebron',
      'location' => 'Hebron, Palestine',
      'summary' => 'An illustrative entrance and reception concept for a commercial building in Hebron, with grey marble and a coordinated natural-stone palette.',
      'body' => '<p>This is an illustrative project concept, not a documented Al-Baron commission. Images are material and design references, not photographs of a verified completed site.</p><p>The proposal uses Galilee Grey marble for a reception feature wall and selected entrance details. A simple joint layout and coordinated stone samples keep the material palette consistent between the doorway, reception desk and circulation areas.</p><p>The architect would review vein direction, panel sizes and access for maintenance. Floor finishes and entrance thresholds would be specified separately for expected foot traffic and accessibility needs.</p>',
      'alt' => 'Natural stone finish samples for an illustrative commercial entrance',
    ],
    'ar' => [
      'title' => 'مدخل مبنى تجاري - الخليل',
      'location' => 'الخليل، فلسطين',
      'summary' => 'تصور توضيحي لمدخل واستقبال مبنى تجاري في الخليل، يجمع الرخام الرمادي مع مجموعة متناسقة من خامات الحجر الطبيعي.',
      'body' => '<p>هذا تصور توضيحي لمشروع، وليس عملا منفذا وموثقا لشركة البارون. الصور مراجع للخامات والتصميم، وليست صورا لموقع مكتمل تم التحقق منه.</p><p>يقترح التصميم استخدام رخام الجليل الرمادي لجدار مميز خلف الاستقبال وبعض تفاصيل المدخل. ويساعد توزيع الفواصل البسيط واعتماد عينات حجرية متناسقة على توحيد الخامات بين الباب ومكتب الاستقبال ومسارات الحركة.</p><p>يراجع المهندس اتجاه التعريق وأحجام الألواح وإمكانية الوصول للصيانة. وتحدد تشطيبات الأرضيات والعتبات بصورة مستقلة بحسب حركة الزوار ومتطلبات سهولة الوصول.</p>',
      'alt' => 'عينات تشطيبات حجرية لتصور مدخل مبنى تجاري',
    ],
  ],
  [
    'legacy_title' => 'Heritage Plaza Landscaping — Bethlehem, Palestine',
    'image' => 'contact-bg.webp',
    'stones' => ['Hebron Beige', 'Desert Travertine'],
    'en' => [
      'title' => 'Courtyard Garden - Nablus',
      'location' => 'Nablus, Palestine',
      'summary' => 'An illustrative courtyard garden concept for Nablus, with textured paving, planted edges and a stone-lined entrance.',
      'body' => '<p>This is an illustrative project concept, not a documented Al-Baron commission. Images are material and design references, not photographs of a verified completed site.</p><p>The proposal combines textured Hebron Beige paving with Desert Travertine accents around planted areas. A stone-lined garden entrance and a modest seating area draw on the enclosed courtyards found in local residential architecture.</p><p>Paving falls, drainage outlets and planting boundaries would be coordinated before installation. Exterior samples would be assessed for wet-weather suitability and maintenance, with final specifications agreed by the project team.</p>',
      'alt' => 'Illustrative stone garden wall and courtyard entrance reference',
    ],
    'ar' => [
      'title' => 'حديقة داخلية - نابلس',
      'location' => 'نابلس، فلسطين',
      'summary' => 'تصور توضيحي لحديقة داخلية في نابلس، بأرضيات حجرية ذات ملمس وحواف مزروعة ومدخل محاط بالحجر الطبيعي.',
      'body' => '<p>هذا تصور توضيحي لمشروع، وليس عملا منفذا وموثقا لشركة البارون. الصور مراجع للخامات والتصميم، وليست صورا لموقع مكتمل تم التحقق منه.</p><p>يجمع المقترح بين أرضيات بيج الخليل ذات الملمس وتفاصيل من ترافرتين الصحراء حول المساحات المزروعة. ويستلهم المدخل الحجري ومنطقة الجلوس البسيطة الأفنية الداخلية في العمارة السكنية المحلية.</p><p>تنسق ميول الأرضيات ومخارج التصريف وحدود الزراعة قبل التركيب. وتراجع العينات الخارجية من حيث ملاءمتها للأجواء الماطرة ومتطلبات الصيانة، وتعتمد المواصفات النهائية بالتعاون مع فريق المشروع.</p>',
      'alt' => 'مرجع توضيحي لجدار حديقة حجري ومدخل فناء',
    ],
  ],
];

$project_storage = \Drupal::entityTypeManager()->getStorage('node');
$project_file_system = \Drupal::service('file_system');
$project_file_storage = \Drupal::entityTypeManager()->getStorage('file');
$project_media_storage = \Drupal::entityTypeManager()->getStorage('media');
$project_alias_generator = \Drupal::service('pathauto.generator');
$project_destination = 'public://editorial/project-examples';
if (!$project_file_system->prepareDirectory($project_destination, FileSystemInterface::CREATE_DIRECTORY)) {
  throw new \RuntimeException('Cannot prepare project example image directory.');
}

foreach ($project_examples as $project_example) {
  $source = DRUPAL_ROOT . '/themes/custom/albaron/' . $project_example['image'];
  if (!is_file($source)) {
    throw new \RuntimeException('Missing project reference image: ' . $source);
  }
  foreach ($project_example['stones'] as $stone_title) {
    if (!$project_storage->loadByProperties(['type' => 'product', 'title' => $stone_title])) {
      throw new \RuntimeException('Missing project stone: ' . $stone_title);
    }
  }
}

$created_proj = 0;
foreach ($project_examples as $project_example) {
  $matches = $project_storage->loadByProperties([
    'type' => 'project',
    'title' => [$project_example['legacy_title'], $project_example['en']['title']],
  ]);
  if (count($matches) > 1) {
    throw new \RuntimeException('Ambiguous project match: ' . $project_example['en']['title']);
  }
  $project = $matches ? reset($matches) : Node::create(['type' => 'project', 'langcode' => 'en', 'status' => 1]);
  if (!$project instanceof Node) {
    throw new \UnexpectedValueException('Expected a project node.');
  }
  $project = $project->getUntranslated();
  $created_proj += (int) $project->isNew();
  $project->setNewRevision(TRUE);
  $project->setRevisionLogMessage('Replace international sample with a clearly labeled Palestinian project concept.');

  $media_name = 'Project reference: ' . $project_example['en']['title'];
  $media_matches = $project_media_storage->loadByProperties(['bundle' => 'image', 'name' => $media_name]);
  $media = $media_matches ? reset($media_matches) : NULL;
  if (!$media) {
    $image_uri = $project_destination . '/' . $project_example['image'];
    $file_matches = $project_file_storage->loadByProperties(['uri' => $image_uri]);
    $file = $file_matches ? reset($file_matches) : NULL;
    if (!$file) {
      $copied_uri = $project_file_system->copy(DRUPAL_ROOT . '/themes/custom/albaron/' . $project_example['image'], $image_uri, FileExists::Replace);
      $file = File::create(['uri' => $copied_uri, 'status' => 1]);
      $file->save();
    }
    $media = Media::create([
      'bundle' => 'image',
      'langcode' => 'en',
      'name' => $media_name,
      'field_media_image' => ['target_id' => $file->id(), 'alt' => $project_example['en']['alt']],
      'status' => 1,
    ]);
    $media->save();
  }

  $stone_references = [];
  foreach ($project_example['stones'] as $stone_title) {
    $stone_matches = $project_storage->loadByProperties(['type' => 'product', 'title' => $stone_title]);
    $stone_references[] = ['target_id' => reset($stone_matches)->id()];
  }
  foreach (['en', 'ar'] as $langcode) {
    if (!\Drupal::languageManager()->getLanguage($langcode)) {
      continue;
    }
    $translation = $project->hasTranslation($langcode) ? $project->getTranslation($langcode) : $project->addTranslation($langcode);
    $copy = $project_example[$langcode];
    $translation->setTitle($copy['title']);
    $translation->set('field_location', $copy['location']);
    $translation->set('field_summary', $copy['summary']);
    $translation->set('body', ['value' => $copy['body'], 'format' => 'basic_html']);
    $translation->set('field_year', []);
    $translation->set('field_images', [['target_id' => $media->id()]]);
    $translation->set('field_stone_used', $stone_references);
    $translation->get('path')->pathauto = \Drupal\pathauto\PathautoState::CREATE;
  }
  $project->save();
  foreach ($project->getTranslationLanguages() as $langcode => $language) {
    $project_alias_generator->updateEntityAlias($project->getTranslation($langcode), 'update', ['force' => TRUE]);
  }
  print 'Saved project ' . $project->id() . ': ' . $project_example['en']['title'] . "\n";
}