<aside>
<?php
//include
include('Donnees.inc.php');

//Obtenez la catégorie actuelle, la valeur par défaut est Aliment
$category = isset($_GET['cat']) ? $_GET['cat'] : 'Aliment';

// Fil d'Ariane
function getBreadcrumb($category, $Hierarchie) {
    $breadcrumb = array();
    while ($category != 'Aliment' && isset($Hierarchie[$category]['super-categorie'][0])) {
        array_unshift($breadcrumb, $category);
        $category = $Hierarchie[$category]['super-categorie'][0];
    }
    array_unshift($breadcrumb, 'Aliment');
    return $breadcrumb;
}

// afficher
$breadcrumb = getBreadcrumb($category, $Hierarchie);
echo '<div class="breadcrumb">';
foreach ($breadcrumb as $index => $cat) {
    if ($index != 0) {
        echo ' &gt; ';
    }
    echo '<a href="index.php?cat=' . urlencode($cat) . '">' . $cat . '</a>';
}
echo '</div>';

// afficher sous categorie
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
