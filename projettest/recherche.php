<?php
// recherche.php

/**
 * 解析用户的搜索查询
 *
 * @param string $query 用户输入的搜索字符串
 * @param array $Hierarchie 食材层次结构数组
 * @return array 包含解析后的想要的食材、不想要的食材和未识别的元素
 */
function parse_search_query($query, $Hierarchie) {
    $desired = [];
    $undesired = [];
    $unrecognized = [];

    // 检查双引号数量
    if (substr_count($query, '"') % 2 !== 0) {
        return ['error' => "Problème de syntaxe dans votre requête : nombre impair de double-quotes"];
    }

    // 使用正则表达式匹配带 + 或 - 的成分，支持双引号包裹的成分
    preg_match_all('/([\+\-]?"[^"]+"|[\+\-]?\S+)/', $query, $matches);
    
    foreach ($matches[0] as $token) {
        $first_char = substr($token, 0, 1);

        // 去除符号和双引号
        $ingredient = trim($token, '+-"');

        if ($first_char === '-') {
            $undesired[] = $ingredient;
        } else {
            $desired[] = $ingredient;
        }
    }

    // 验证食材
    list($valid_desired, $invalid_desired) = validate_ingredients($desired, $Hierarchie);
    list($valid_undesired, $invalid_undesired) = validate_ingredients($undesired, $Hierarchie);

    $unrecognized = array_merge($invalid_desired, $invalid_undesired);

    return [
        'desired' => $valid_desired,
        'undesired' => $valid_undesired,
        'unrecognized' => $unrecognized,
        'error' => ''
    ];
}

/**
 * 验证食材是否存在于食材层次结构中
 *
 * @param array $ingredients 要验证的食材列表
 * @param array $Hierarchie 食材层次结构数组
 * @return array 包含有效的食材和无效的食材
 */
function validate_ingredients($ingredients, $Hierarchie) {
    $valid = [];
    $invalid = [];

    foreach ($ingredients as $ingredient) {
        // 将食材名称标准化
        $ingredient_normalized = ucfirst(strtolower($ingredient));

        if (array_key_exists($ingredient_normalized, $Hierarchie)) {
            $valid[] = $ingredient_normalized;
        } else {
            $invalid[] = $ingredient;
        }
    }

    return [$valid, $invalid];
}

/**
 * 执行食谱搜索
 *
 * @param array $desired_ingredients 想要的食材列表
 * @param array $undesired_ingredients 不想要的食材列表
 * @param array $Recettes 食谱数组
 * @param array $Hierarchie 食材层次结构数组
 * @return array 搜索结果，包含匹配的食谱和满意度分数
 */
function search_recipes($desired_ingredients, $undesired_ingredients, $Recettes, $Hierarchie) {
    $results = [];

    foreach ($Recettes as $recette) {
        $recipe_ingredients = $recette['index'];

        // 将食谱的食材扩展为包括所有子食材
        $expanded_ingredients = [];
        foreach ($recipe_ingredients as $ing) {
            $expanded_ingredients = array_merge($expanded_ingredients, getAllSubIngredients($ing, $Hierarchie));
        }
        $expanded_ingredients = array_unique($expanded_ingredients);

        // 检查是否包含不想要的食材
        if (array_intersect($undesired_ingredients, $expanded_ingredients)) {
            continue; // 跳过包含不想要食材的食谱
        }

        // 计算匹配的想要的食材数量
        $matched_desired = array_intersect($desired_ingredients, $expanded_ingredients);
        $matched_count = count($matched_desired);
        $total_criteria = count($desired_ingredients);

        if ($total_criteria > 0) {
            $score = ($matched_count / $total_criteria) * 100;
        } else {
            $score = 0;
        }

        // **修改这里：将所有食谱都加入结果**
        $recette['score'] = $score;
        $results[] = $recette;
    }

    // 按照满意度分数排序
    usort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    return $results;
}


/**
 * 获取指定食材的所有子食材
 *
 * @param string $ingredient 食材名称
 * @param array $Hierarchie 食材层次结构数组
 * @return array 包含所有子食材的数组
 */
function getAllSubIngredients($ingredient, $Hierarchie) {
    $ingredients = [$ingredient];

    if (isset($Hierarchie[$ingredient]['sous-categorie'])) {
        foreach ($Hierarchie[$ingredient]['sous-categorie'] as $sub) {
            $ingredients = array_merge($ingredients, getAllSubIngredients($sub, $Hierarchie));
        }
    }

    return $ingredients;
}

/**
 * 执行完整的搜索流程
 *
 * @param string $query 用户输入的搜索字符串
 * @param array $Recettes 食谱数组
 * @param array $Hierarchie 食材层次结构数组
 * @return array 包含解析结果和搜索结果
 */
function perform_search($query, $Recettes, $Hierarchie) {
    $search_results = [];
    $parsed_query = parse_search_query($query, $Hierarchie);

    if (!empty($parsed_query['error'])) {
        return [
            'error_message' => $parsed_query['error'],
            'search_results' => []
        ];
    }

    $desired_ingredients = $parsed_query['desired'];
    $undesired_ingredients = $parsed_query['undesired'];
    $unrecognized_elements = $parsed_query['unrecognized'];

    if (empty($desired_ingredients) && empty($undesired_ingredients)) {
        return [
            'error_message' => "Problème dans votre requête : recherche impossible",
            'desired_ingredients' => $desired_ingredients,
            'undesired_ingredients' => $undesired_ingredients,
            'unrecognized_elements' => $unrecognized_elements,
            'search_results' => []
        ];
    }

    $search_results = search_recipes($desired_ingredients, $undesired_ingredients, $Recettes, $Hierarchie);

    return [
        'error_message' => '',
        'desired_ingredients' => $desired_ingredients,
        'undesired_ingredients' => $undesired_ingredients,
        'unrecognized_elements' => $unrecognized_elements,
        'search_results' => $search_results
    ];
}
?>
