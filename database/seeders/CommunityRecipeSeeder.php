<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CommunityRecipeSeeder extends Seeder
{
    public function run(): void
    {
        // Use existing community users (IDs 15-18) or create them if missing
        $communityUsers = [
            ['id' => 15, 'name' => 'Maria Santos',  'username' => 'maria_s',   'email' => 'maria@ulam.ph'],
            ['id' => 16, 'name' => 'Lito Flores',   'username' => 'lito_f',    'email' => 'litof@ulam.ph'],
            ['id' => 17, 'name' => 'Ana Reyes',     'username' => 'ana_r',     'email' => 'ana@ulam.ph'],
            ['id' => 18, 'name' => 'Mang Eddie',    'username' => 'mang_eddie','email' => 'eddie@ulam.ph'],
        ];

        $users = [];
        foreach ($communityUsers as $u) {
            $user = User::find($u['id'])
                ?? User::where('email', $u['email'])->first()
                ?? User::create([
                    'name'     => $u['name'],
                    'username' => $u['username'],
                    'email'    => $u['email'],
                    'password' => Hash::make('password123'),
                ]);
            $users[] = $user;
        }

        $recipes = [
            // Maria Santos (users[0])
            [
                'author'      => 0,
                'title'       => 'Mama\'s Chicken Sotanghon Soup',
                'description' => 'Glass noodle soup with shredded chicken and veggies — my mom\'s recipe that heals any cold or tummy ache.',
                'budget_tag'  => 'budget_200',
                'cost'        => 170,
                'servings'    => 4,
                'prep'        => 10,
                'cook'        => 25,
                'difficulty'  => 'easy',
                'tags'        => ['manok', 'sabaw', 'mabilis'],
                'steps'       => [
                    'Sauté garlic, onion, and ginger in oil until soft.',
                    'Add chicken strips and cook until white throughout.',
                    'Pour in chicken broth or water with broth cubes.',
                    'Add soaked sotanghon noodles and fish sauce.',
                    'Simmer 5 minutes. Garnish with green onion and fried garlic.',
                ],
                'ingredients' => [
                    ['name' => 'Chicken breast (shredded)', 'qty' => '200g',  'price' => 60],
                    ['name' => 'Sotanghon noodles',  'qty' => '80g',          'price' => 20],
                    ['name' => 'Chicken broth',      'qty' => '4 cups',       'price' => 20],
                    ['name' => 'Ginger',             'qty' => '1 inch',       'price' => 5],
                    ['name' => 'Garlic, onion',      'qty' => '4 cloves, 1 pc','price' => 12],
                    ['name' => 'Fish sauce',         'qty' => '2 tbsp',       'price' => 5],
                    ['name' => 'Green onion',        'qty' => '2 stalks',     'price' => 8],
                ],
            ],
            [
                'author'      => 0,
                'title'       => 'Creamy Pork Sinigang sa Gata',
                'description' => 'A Batangas twist on sinigang — the sour broth gets swirled with coconut cream at the end for a rich, velvety finish.',
                'budget_tag'  => 'budget_400',
                'cost'        => 360,
                'servings'    => 5,
                'prep'        => 15,
                'cook'        => 45,
                'difficulty'  => 'medium',
                'tags'        => ['baboy', 'sabaw', 'espesyal'],
                'steps'       => [
                    'Boil pork ribs until tender, about 30 minutes.',
                    'Add sinigang mix, tomatoes, and labanos. Simmer 10 minutes.',
                    'Add sitaw and kangkong.',
                    'Stir in coconut cream and simmer 5 more minutes.',
                    'Season with fish sauce. Serve hot — do not boil after adding gata!',
                ],
                'ingredients' => [
                    ['name' => 'Pork ribs',           'qty' => '½ kg',      'price' => 180],
                    ['name' => 'Sinigang mix',        'qty' => '1 pack',    'price' => 12],
                    ['name' => 'Coconut cream',       'qty' => '200ml',     'price' => 35],
                    ['name' => 'Labanos, sitaw, kangkong', 'qty' => 'as needed','price' => 45],
                    ['name' => 'Tomatoes',            'qty' => '3 pcs',     'price' => 20],
                    ['name' => 'Fish sauce',          'qty' => 'to taste',  'price' => 5],
                ],
            ],
            [
                'author'      => 0,
                'title'       => 'Ginataang Bilo-Bilo',
                'description' => 'Sweet coconut milk dessert with sticky rice balls, jackfruit, and saging na saba. My kids\' favorite merienda!',
                'budget_tag'  => 'budget_200',
                'cost'        => 140,
                'servings'    => 6,
                'prep'        => 20,
                'cook'        => 15,
                'difficulty'  => 'easy',
                'tags'        => ['espesyal', 'mabilis'],
                'steps'       => [
                    'Mix glutinous rice flour with water to form dough. Roll into small balls.',
                    'Boil coconut milk with sugar and a pinch of salt.',
                    'Drop bilo-bilo into the boiling coconut milk.',
                    'Add saging na saba slices and jackfruit strips.',
                    'Cook until bilo-bilo floats (about 5 minutes). Serve warm or cold.',
                ],
                'ingredients' => [
                    ['name' => 'Glutinous rice flour', 'qty' => '1 cup',   'price' => 25],
                    ['name' => 'Coconut milk (gata)',  'qty' => '400ml',   'price' => 45],
                    ['name' => 'Saging na saba',       'qty' => '3 pcs',   'price' => 20],
                    ['name' => 'Jackfruit (langka)',   'qty' => '½ cup',   'price' => 20],
                    ['name' => 'Sugar',                'qty' => '½ cup',   'price' => 15],
                ],
            ],
            // Lito Flores (users[1])
            [
                'author'      => 1,
                'title'       => 'Budget Palabok',
                'description' => 'Classic Filipino noodle dish with shrimp sauce, crispy chicharon, and egg — recreated on a shoestring budget.',
                'budget_tag'  => 'budget_200',
                'cost'        => 195,
                'servings'    => 4,
                'prep'        => 20,
                'cook'        => 25,
                'difficulty'  => 'medium',
                'tags'        => ['espesyal', 'klasiko', 'tradisyunal'],
                'steps'       => [
                    'Soak bihon noodles in water, then boil 3 minutes. Drain and set aside.',
                    'Make sauce: sauté garlic, add shrimp broth or water, achuete water, fish sauce.',
                    'Thicken sauce with cornstarch dissolved in water.',
                    'Arrange noodles on plate, pour sauce over top.',
                    'Top with chicharon, spring onion, hard-boiled egg, and calamansi.',
                ],
                'ingredients' => [
                    ['name' => 'Bihon noodles',       'qty' => '200g',      'price' => 35],
                    ['name' => 'Shrimp (hipon)',       'qty' => '100g',      'price' => 50],
                    ['name' => 'Chicharon (crushed)', 'qty' => '½ cup',      'price' => 35],
                    ['name' => 'Achuete powder',      'qty' => '1 tsp',     'price' => 5],
                    ['name' => 'Hard-boiled eggs',    'qty' => '2 pcs',     'price' => 16],
                    ['name' => 'Fish sauce, cornstarch', 'qty' => 'as needed','price' => 10],
                    ['name' => 'Spring onion, calamansi', 'qty' => 'as needed','price' => 15],
                ],
            ],
            [
                'author'      => 1,
                'title'       => 'Pork Tocino (Homemade)',
                'description' => 'Sweet cured pork made from scratch without preservatives. Cheaper than store-bought and so much better!',
                'budget_tag'  => 'budget_200',
                'cost'        => 185,
                'servings'    => 3,
                'prep'        => 10,
                'cook'        => 15,
                'difficulty'  => 'easy',
                'tags'        => ['baboy', 'mabilis', 'espesyal'],
                'steps'       => [
                    'Mix sugar, salt, garlic, and a pinch of baking soda in a bowl.',
                    'Toss thinly sliced pork with the cure mixture.',
                    'Refrigerate at least overnight (24-48 hours is better).',
                    'Pan-fry in a little oil and water. The water evaporates and caramelizes the sugar.',
                    'Serve with garlic rice, egg, and atchara.',
                ],
                'ingredients' => [
                    ['name' => 'Pork shoulder (thin sliced)', 'qty' => '300g', 'price' => 120],
                    ['name' => 'Sugar',                'qty' => '3 tbsp',   'price' => 8],
                    ['name' => 'Salt',                 'qty' => '1 tsp',    'price' => 2],
                    ['name' => 'Garlic (minced)',      'qty' => '4 cloves', 'price' => 5],
                    ['name' => 'Baking soda',          'qty' => 'pinch',    'price' => 2],
                    ['name' => 'Egg (for serving)',    'qty' => '1 pc',     'price' => 8],
                ],
            ],
            // Ana Reyes (users[2])
            [
                'author'      => 2,
                'title'       => 'Adobong Kangkong',
                'description' => 'Water spinach cooked adobo-style in soy sauce, vinegar, and garlic. Ready in 10 minutes and SO good with rice!',
                'budget_tag'  => 'budget_100',
                'cost'        => 55,
                'servings'    => 2,
                'prep'        => 5,
                'cook'        => 10,
                'difficulty'  => 'easy',
                'tags'        => ['gulay', 'mabilis', 'masustansya'],
                'steps'       => [
                    'Heat oil and fry garlic until golden and crispy.',
                    'Add kangkong stems first, stir-fry 2 minutes.',
                    'Add the leaves and pour in soy sauce and vinegar.',
                    'Toss quickly — do not overcook! Kangkong wilts fast.',
                    'Serve immediately over rice.',
                ],
                'ingredients' => [
                    ['name' => 'Kangkong (water spinach)', 'qty' => '2 bundles', 'price' => 30],
                    ['name' => 'Garlic',              'qty' => '5 cloves',  'price' => 5],
                    ['name' => 'Soy sauce',           'qty' => '2 tbsp',   'price' => 5],
                    ['name' => 'Vinegar',             'qty' => '1 tbsp',   'price' => 3],
                    ['name' => 'Cooking oil',         'qty' => '2 tbsp',   'price' => 5],
                ],
            ],
            [
                'author'      => 2,
                'title'       => 'Champorado (Chocolate Rice Porridge)',
                'description' => 'Sweet chocolate rice porridge made with tablea — the ultimate Pinoy breakfast paired with tuyo (dried fish).',
                'budget_tag'  => 'budget_100',
                'cost'        => 75,
                'servings'    => 3,
                'prep'        => 5,
                'cook'        => 20,
                'difficulty'  => 'easy',
                'tags'        => ['mabilis', 'klasiko'],
                'steps'       => [
                    'Wash glutinous rice and place in pot with water (2:1 water to rice ratio).',
                    'Cook on medium heat, stirring occasionally, until rice is very soft.',
                    'Dissolve tablea in a little hot water and add to the pot.',
                    'Stir in sugar to taste. Cook 5 more minutes until thick.',
                    'Serve warm with a splash of evaporated milk and crispy tuyo on the side.',
                ],
                'ingredients' => [
                    ['name' => 'Glutinous rice',     'qty' => '1 cup',    'price' => 25],
                    ['name' => 'Tablea (cacao tablets)', 'qty' => '3 pcs','price' => 25],
                    ['name' => 'Sugar',              'qty' => '3 tbsp',   'price' => 8],
                    ['name' => 'Evaporated milk',    'qty' => '¼ cup',    'price' => 12],
                ],
            ],
            // Mang Eddie (users[3])
            [
                'author'      => 3,
                'title'       => 'Bangus Sisig',
                'description' => 'Bangus (milkfish) version of the famous Pampanga sisig — cheaper than pork, just as sizzling and crunchy!',
                'budget_tag'  => 'budget_200',
                'cost'        => 195,
                'servings'    => 3,
                'prep'        => 15,
                'cook'        => 20,
                'difficulty'  => 'medium',
                'tags'        => ['isda', 'espesyal', 'tradisyunal'],
                'steps'       => [
                    'Grill or fry bangus until fully cooked. Remove all bones carefully.',
                    'Flake the fish into small pieces.',
                    'Sauté garlic and onion in butter. Add fish flakes.',
                    'Add calamansi juice, soy sauce, and siling labuyo.',
                    'Serve on a sizzling plate topped with a raw egg. Mix before eating!',
                ],
                'ingredients' => [
                    ['name' => 'Bangus (milkfish)',   'qty' => '1 large pc','price' => 100],
                    ['name' => 'Onion (white, diced)','qty' => '1 large pc','price' => 12],
                    ['name' => 'Garlic',              'qty' => '4 cloves',  'price' => 5],
                    ['name' => 'Calamansi',           'qty' => '6 pcs',     'price' => 15],
                    ['name' => 'Soy sauce',           'qty' => '2 tbsp',   'price' => 5],
                    ['name' => 'Siling labuyo',       'qty' => '3 pcs',    'price' => 8],
                    ['name' => 'Butter',              'qty' => '1 tbsp',   'price' => 15],
                    ['name' => 'Egg',                 'qty' => '1 pc',     'price' => 8],
                ],
            ],
            [
                'author'      => 3,
                'title'       => 'Tinolang Manok (Mang Eddie\'s Style)',
                'description' => 'My version with extra ginger and lemongrass — the broth is incredibly fragrant. The secret is adding the chili leaves at the very end.',
                'budget_tag'  => 'budget_200',
                'cost'        => 190,
                'servings'    => 4,
                'prep'        => 10,
                'cook'        => 30,
                'difficulty'  => 'easy',
                'tags'        => ['manok', 'sabaw', 'masustansya'],
                'steps'       => [
                    'Pound ginger to release its oils. Sauté with garlic and onion.',
                    'Add lemongrass stalks (bruised) for extra fragrance.',
                    'Add chicken pieces and cook until lightly browned.',
                    'Pour in water or broth. Bring to boil and simmer 20 minutes.',
                    'Add green papaya pieces. Cook until tender.',
                    'Turn off heat and add chili leaves (dahon ng siling labuyo). Cover 2 minutes.',
                    'Season with fish sauce. Serve immediately.',
                ],
                'ingredients' => [
                    ['name' => 'Chicken (bone-in)',   'qty' => '700g',      'price' => 100],
                    ['name' => 'Green papaya',        'qty' => '½ pc',      'price' => 20],
                    ['name' => 'Ginger (big knob)',   'qty' => '3 inch',    'price' => 10],
                    ['name' => 'Lemongrass (tanglad)','qty' => '2 stalks',  'price' => 8],
                    ['name' => 'Chili leaves',        'qty' => '1 cup',     'price' => 10],
                    ['name' => 'Garlic, onion',       'qty' => '4 cloves, 1 pc','price' => 12],
                    ['name' => 'Fish sauce',          'qty' => '2 tbsp',    'price' => 5],
                ],
            ],
            [
                'author'      => 3,
                'title'       => 'Corned Beef Guisado with Potatoes',
                'description' => 'Canned corned beef elevated with sautéed potatoes, tomatoes, and eggs. A 20-minute budget miracle.',
                'budget_tag'  => 'budget_100',
                'cost'        => 90,
                'servings'    => 3,
                'prep'        => 5,
                'cook'        => 15,
                'difficulty'  => 'easy',
                'tags'        => ['mabilis', 'espesyal'],
                'steps'       => [
                    'Fry diced potatoes in oil until golden and crispy. Set aside.',
                    'In same pan, sauté garlic, onion, and tomatoes.',
                    'Add canned corned beef and break it apart.',
                    'Return potatoes to the pan. Stir-fry 3 minutes.',
                    'Push everything to the side, scramble eggs into the pan, then mix all together.',
                    'Season with black pepper. Serve with garlic rice.',
                ],
                'ingredients' => [
                    ['name' => 'Canned corned beef (175g)', 'qty' => '1 can', 'price' => 55],
                    ['name' => 'Potato (diced)',      'qty' => '1 large',   'price' => 15],
                    ['name' => 'Eggs',                'qty' => '2 pcs',     'price' => 16],
                    ['name' => 'Garlic, onion, tomato','qty' => 'as needed','price' => 12],
                ],
            ],
        ];

        foreach ($recipes as $data) {
            $author = $users[$data['author']];

            $recipe = Recipe::create([
                'user_id'           => $author->id,
                'title'             => $data['title'],
                'description'       => $data['description'],
                'source'            => 'community',
                'budget_tag'        => $data['budget_tag'],
                'estimated_cost'    => $data['cost'],
                'servings'          => $data['servings'],
                'prep_time_minutes' => $data['prep'],
                'cook_time_minutes' => $data['cook'],
                'difficulty'        => $data['difficulty'],
                'tags'              => $data['tags'],
                'steps'             => $data['steps'],
                'is_published'      => true,
                'is_premium_only'   => false,
            ]);

            foreach ($data['ingredients'] as $i => $ing) {
                RecipeIngredient::create([
                    'recipe_id'       => $recipe->id,
                    'name'            => $ing['name'],
                    'quantity'        => $ing['qty'],
                    'unit'            => '',
                    'estimated_price' => $ing['price'],
                    'sort_order'      => $i,
                ]);
            }
        }

        $this->command->info('Seeded 10 community recipes from 4 community users.');
    }
}
