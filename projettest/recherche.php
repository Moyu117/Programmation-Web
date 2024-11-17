<?php
// recherche.php

/*
 * Analyser la requête de recherche d'un utilisateur
 *
 * $query Chaîne de recherche saisie par l'utilisateur
 * $Hierarchie Niveau d'ingrédient
 * returner les ingrédients souhaités analysés, les ingrédients indésirables et les éléments non reconnus
 */
function parse_search_query($query, $Hierarchie) {
    $desired = [];
    $undesired = [];
    $unrecognized = [];
    $error_message = '';
    // Vérifiez “ ”
    if (substr_count($query, '"') % 2 !== 0) {
        $error_message = "Problème de syntaxe dans votre requête : nombre impair de double-quotes";
        return [
            'desired' => $desired,
            'undesired' => $undesired,
            'unrecognized' => $unrecognized,
            'error' => $error_message
        ];
    }

    //  + ，- 
    preg_match_all('/([\+\-]?"[^"]+"|[\+\-]?\S+)/', $query, $matches);
    
    foreach ($matches[0] as $token) {
        $first_char = substr($token, 0, 1);

        // Supprimer les symboles et les “”
        $ingredient = trim($token, '+-"');

        if ($first_char === '-') {
            $undesired[] = $ingredient;
        } else {
            $desired[] = $ingredient;
        }
    }

    // verifier
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

/*
 * Vérifier qu'un ingrédient existe dans la hiérarchie des ingrédients
 *
 *  $ingredients Liste des ingrédients à vérifier
 *  $Hierarchie Hierarchie Niveau d'ingrédient
 * return  utile et non utile
 */
function validate_ingredients($ingredients, $Hierarchie) {
    $valid = [];
    $invalid = [];

    foreach ($ingredients as $ingredient) {
        // nom des aliment
        $ingredient_normalized = ucfirst(strtolower($ingredient));

        if (array_key_exists($ingredient_normalized, $Hierarchie)) {
            $valid[] = $ingredient_normalized;
        } else {
            $invalid[] = $ingredient;
        }
    }

    return [$valid, $invalid];
}

/*
 * une recherche de recette
 *
 * $desired_ingredients Liste des souhaités
 * $undesired_ingredients Liste des non souhaités
 * $Recettes 
 * $Hierarchie hiérarchique des ingrédients
 * returner Résultats de recherche avec recettes et note de satisfaction
 */
function search_recipes($desired_ingredients, $undesired_ingredients, $Recettes, $Hierarchie) {
    $results = [];

    foreach ($Recettes as $recette) {
        $recipe_ingredients = $recette['index'];

        // Développez les ingrédients d'une recette pour tous les sous-ingrédients
        $expanded_ingredients = [];
        foreach ($recipe_ingredients as $ing) {
            $expanded_ingredients = array_merge($expanded_ingredients, getAllSubIngredients($ing, $Hierarchie));
        }
        $expanded_ingredients = array_unique($expanded_ingredients);

        // Ignorer les recettes contenant des ingrédients indésirables
        if (array_intersect($undesired_ingredients, $expanded_ingredients)) {
            continue;
        }

        // Calculer le nombre d'ingrédients souhaités correspondants
        $matched_desired = array_intersect($desired_ingredients, $expanded_ingredients);
        $matched_count = count($matched_desired);
        $total_criteria = count($desired_ingredients);

        if ($total_criteria > 0) {
            $score = ($matched_count / $total_criteria) * 100;
        } else {
            $score = 0;
        }
        $recette['score'] = $score;
        $results[] = $recette;
    }

    // Trier par note de satisfaction
    usort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    return $results;
}


/*
 * Obtenez tous les sous-ingrédients de l'ingrédient spécifié
 *
 * $ingredient Nom de l'ingrédient
 * $Hierarchie hiérarchique des ingrédients
 * returner tableau contenant tous les sous-ingrédients
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
 * le processus de recherche complet
 *
 * $query Chaîne de recherche saisie par l'utilisateur
 * $Recettes Tableau de recettes
 * $Hierarchie  hiérarchique des ingrédients
 * returner res
 */
function perform_search($query, $Recettes, $Hierarchie) {
    $search_results = [];
    $desired_ingredients = [];
    $undesired_ingredients = [];
    $unrecognized_elements = [];
    $error_message = '';

    //verifer
    $parsed_query = parse_search_query($query, $Hierarchie);

    if (!empty($parsed_query['error'])) {
        return [
            'error_message' => $parsed_query['error'],
            'desired_ingredients' => $desired_ingredients,
            'undesired_ingredients' => $undesired_ingredients,
            'unrecognized_elements' => $unrecognized_elements,
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
