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

// 显示面包屑导航
$breadcrumb = getBreadcrumb($category, $Hierarchie);
echo '<div class="breadcrumb">';
foreach ($breadcrumb as $index => $cat) {
    if ($index != 0) {
        echo ' &gt; ';
    }
    echo '<a href="index.php?cat=' . urlencode($cat) . '">' . $cat . '</a>';
}
echo '</div>';

// 显示当前类别的直接子类别
echo '<h2>sous catégorie：</h2>';
if (isset($Hierarchie[$category]['sous-categorie'])) {
    echo '<ul>';
    foreach ($Hierarchie[$category]['sous-categorie'] as $subcat) {
        echo '<li><a href="index.php?cat=' . urlencode($subcat) . '">' . $subcat . '</a></li>';
    }
    echo '</ul>';
} else {
    echo '<p>Non sous catégories</p>';
}
?>
</aside>
