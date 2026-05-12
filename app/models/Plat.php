<?php

class Plat
{
    private static array $plats = [
        ['id' => 1, 'name' => 'Risotto fruits de mer', 'category' => 'Plats', 'price' => 18.90, 'image' => 'Fruits de mer.jpg', 'description' => 'Un risotto crémeux aux fruits de mer, parfumé au safran.'],
        ['id' => 2, 'name' => 'Poulet mayo gourmand', 'category' => 'Plats', 'price' => 12.90, 'image' => 'Poulet mayo.jpg', 'description' => 'Poulet croustillant nappé d’une mayonnaise maison onctueuse.'],
        ['id' => 3, 'name' => 'Poisson fumé rôti', 'category' => 'Plats', 'price' => 16.50, 'image' => 'Poisson fumé.jpg', 'description' => 'Poisson fumé délicat servi avec une touche de citron.'],
        ['id' => 4, 'name' => 'Spaghetti bolognaise', 'category' => 'Plats', 'price' => 14.90, 'image' => 'spaghetti bolognaise.jpg', 'description' => 'Spaghetti classiques servis avec une sauce bolognaise riche.'],
        ['id' => 5, 'name' => 'Riz blanc parfumé', 'category' => 'Accompagnements', 'price' => 4.90, 'image' => 'Riz blanc.jpg', 'description' => 'Riz blanc léger et parfumé pour accompagner chaque plat.'],
        ['id' => 6, 'name' => 'Frites dorées', 'category' => 'Accompagnements', 'price' => 5.50, 'image' => 'Frites.jpg', 'description' => 'Frites croustillantes servies bien chaudes.'],
        ['id' => 7, 'name' => 'Gâteau au chocolat', 'category' => 'Desserts', 'price' => 7.50, 'image' => 'Gateau au chocolat.jpg', 'description' => 'Gâteau au chocolat fondant, idéal pour terminer en douceur.'],
        ['id' => 8, 'name' => 'Jus de fruit frais', 'category' => 'Boissons', 'price' => 4.90, 'image' => 'Jus de fruit.jpg', 'description' => 'Jus de fruits frais pressés, riche en saveurs naturelles.'],
        ['id' => 9, 'name' => 'Cocktail de fruits', 'category' => 'Boissons', 'price' => 5.90, 'image' => 'Coktail de fruit.jpg', 'description' => 'Cocktail fruité et rafraîchissant, parfait pour accompagner le repas.'],
    ];

    public static function all(): array
    {
        return self::$plats;
    }

    public static function getByCategory(): array
    {
        $categories = [];
        foreach (self::$plats as $plat) {
            $categories[$plat['category']][] = $plat;
        }
        return $categories;
    }

    public static function getById(int $id): ?array
    {
        foreach (self::$plats as $plat) {
            if ($plat['id'] === $id) {
                return $plat;
            }
        }
        return null;
    }
}

