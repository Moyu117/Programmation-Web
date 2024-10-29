<aside>
    <?php
// 引入数据文件
include('Donnees.inc.php');

// 获取当前类别，默认为'Aliment'
$category = isset($_GET['cat']) ? $_GET['cat'] : 'Aliment';

// 获取面包屑路径
function getBreadcrumb($category, $Hierarchie) {
    $breadcrumb = array();
    while ($category != 'Aliment' && isset($Hierarchie[$category]['super-categorie'][0])) {
        array_unshift($breadcrumb, $category);
        $category = $Hierarchie[$category]['super-categorie'][0];
    }
    array_unshift($breadcrumb, 'Aliment');
    return $breadcrumb;
}

// 获取所有子类别（递归）
function getAllSubcategories($category, $Hierarchie) {
    $subcategories = array();
    if (isset($Hierarchie[$category]['sous-categorie'])) {
        foreach ($Hierarchie[$category]['sous-categorie'] as $subcat) {
            $subcategories[] = $subcat;
            $subcategories = array_merge($subcategories, getAllSubcategories($subcat, $Hierarchie));
        }
    }
    return $subcategories;
}

// 显示面包屑导航
$breadcrumb = getBreadcrumb($category, $Hierarchie);
echo '<div class="breadcrumb">';
foreach ($breadcrumb as $index => $cat) {
    if ($index != 0) {
        echo ' &gt; ';
    }
    echo '<a href="aside.php?cat=' . urlencode($cat) . '">' . $cat . '</a>';
}
echo '</div>';

// 显示当前类别的直接子类别
echo '<h2>子类别：</h2>';
if (isset($Hierarchie[$category]['sous-categorie'])) {
    echo '<ul>';
    foreach ($Hierarchie[$category]['sous-categorie'] as $subcat) {
        echo '<li><a href="aside.php?cat=' . urlencode($subcat) . '">' . $subcat . '</a></li>';
    }
    echo '</ul>';
} else {
    echo '<p>没有子类别。</p>';
}

// 获取当前类别及其所有子类别
$allCategories = array_merge(array($category), getAllSubcategories($category, $Hierarchie));

// 显示使用当前类别及其子类别作为原料的食谱
echo '<h2>包含 ' . $category . ' 或其子类别的食谱：</h2>';
echo '<ul>';
foreach ($Recettes as $recette) {
    $ingredients = $recette['index'];
    if (array_intersect($allCategories, $ingredients)) {
        echo '<li>' . $recette['titre'] . '</li>';
    }
}
echo '</ul>';
?>

</aside>
