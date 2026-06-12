<?php

declare(strict_types=1);

namespace App\Data;

final class MenuSeed
{
    /** @return list<array{0:string,1:string,2:float,3:?string,4:string}> */
    public static function items(): array
    {
        return [
            ['Pizza Margherita', 'Plats principaux', 24, 'Pizza.jpg', 'plat'],
            ['Tacos Maison', 'Plats principaux', 27, 'Tacos.jpg', 'plat'],
            ['Poulet Mayo', 'Plats principaux', 22, 'Poulet mayo.jpg', 'plat'],
            ['Spaghetti Bolognaise', 'Plats principaux', 19, 'spaghetti bolognaise.jpg', 'plat'],
            ['Fried Rice', 'Plats principaux', 18, 'Fried rice.jpg', 'plat'],
            ['Crevettes Sautées', 'Plats principaux', 34, 'Crevetes.jpg', 'plat'],
            ['Poisson Grillé', 'Plats principaux', 36, 'poisson ambassade.jpg', 'plat'],
            ['Poisson Fumé', 'Plats principaux', 28, 'Poisson fumé.jpg', 'plat'],
            ['Ntaba', 'Plats principaux', 30, 'Ntaba.jpg', 'plat'],
            ['Poisson Salé', 'Plats principaux', 26, 'Poisson salé.jpg', 'plat'],
            ['Poulet Rôti', 'Plats principaux', 23, 'poulet.jpg', 'plat'],
            ['Macaroni Saucisse', 'Plats principaux', 20, 'pates aux saucisses.png', 'plat'],
            ['Saucisses Grillées', 'Plats principaux', 17, 'Saucisses.jpg', 'plat'],
            ['Combo Burger Poulet', 'Plats principaux', 29, 'combo burger frites poulet.jpg', 'plat'],
            ['Fufu et Sauce', 'Plats principaux', 16, 'Fufu.jpg', 'plat'],
            ['Burger Maison', 'Plats principaux', 21, 'KFC.jpg', 'plat'],
            ['Saucisses & Frites', 'Plats principaux', 26, 'Saucisses frites.jpg', 'plat'],
            ['Makoso', 'Plats principaux', 25, 'makoso.jpg', 'plat'],
            ['Samoussa', 'Apéritifs', 6, 'Samoussa.jpg', 'plat'],
            ['Croquettes au fromage', 'Apéritifs', 5, 'croque monsieur.png', 'plat'],
            ['Croquettes aux pommes de terre', 'Apéritifs', 5, 'croquettes aux pommes de terre.png', 'plat'],
            ['4 Petits pains', 'Apéritifs', 6, 'petits pains.png', 'plat'],
            ['3 Croissants au beurre', 'Apéritifs', 6, 'pancakes.png', 'plat'],
            ['Salade Verte', 'Entrées', 8, 'salade aux légumes.png', 'plat'],
            ['Soupe du Jour', 'Entrées', 9, 'soupes aux légumes.png', 'plat'],
            ['Salade Avocat', 'Entrées', 8, 'salade avocat.png', 'plat'],
            ['Carpaccio de Boeuf', 'Entrées', 12, 'bouillon à la viande de boeuf.png', 'plat'],
            ['Combo 2 Burger + frites + coca', 'Combo', 55, 'combo 2burgers frites et coca.png', 'plat'],
            ['Combo Burger', 'Combo', 28, 'combo burger frites poulet.jpg', 'plat'],
            ['Combo Sandwich', 'Combo', 30, 'combo sandwich frites.png', 'plat'],
            ['Combo Croque monsieur', 'Combo', 32, 'combo 3croques monsieur frites et mojito.png', 'plat'],
            ['Gâteau au Chocolat', 'Desserts', 7, 'Gateau au chocolat.jpg', 'plat'],
            ['Glace à la Banane', 'Desserts', 6, 'glace a la banane.jpg', 'plat'],
            ['Churros', 'Desserts', 6, 'spring au chocolat.png', 'plat'],
            ['Salade de fruit', 'Desserts', 7, 'salade de fruit.png', 'plat'],
            ['Crepes au chocolat', 'Desserts', 7, 'crepes au chocolat.jpg', 'plat'],
            ['Tarte aux pommes', 'Desserts', 7, 'tarte aux pommes.jpg', 'plat'],
            ['Frites', 'Accompagnements', 4, 'Frites.jpg', 'plat'],
            ['Fufu', 'Accompagnements', 4, 'Fufu.jpg', 'plat'],
            ['Riz Blanc', 'Accompagnements', 3, 'Riz blanc.jpg', 'plat'],
            ['Pommes de Terre', 'Accompagnements', 4, 'Pomme de terre.jpg', 'plat'],
            ['Chikwangue', 'Accompagnements', 4, 'Chikwangue.jpg', 'plat'],
            ['Bananes Plantain', 'Accompagnements', 5, 'Bananes.jpg', 'plat'],
            ['Jus de Fruit', 'Boissons', 4, 'Jus de fruit.jpg', 'boisson'],
            ['Milkshake', 'Boissons', 5, 'Milkshakes.jpg', 'boisson'],
            ['Cocktail de Fruits', 'Boissons', 5, 'Coktail de fruit.jpg', 'boisson'],
            ['Smoothie Banane', 'Boissons', 5, 'glace a la banane.jpg', 'boisson'],
            ['Coca-Cola, Fanta, Sprite', 'Boissons', 3, 'boissons coca cola.png', 'boisson'],
            ['Eau Minérale', 'Boissons', 2, null, 'boisson'],
            ['Pinacolada', 'Boissons', 3, 'pinnacolada.png', 'boisson'],
            ['Mojito', 'Boissons', 3, 'mojito.png', 'boisson'],
            ['Jack Daniels', 'Boissons', 4, 'whisky jack daniel.jpg', 'boisson'],
            ['Red Label', 'Boissons', 5, 'whisky red label.jpg', 'boisson'],
            ['Heinekein', 'Boissons', 5, 'bierre heinekein.jpg', 'boisson'],
        ];
    }
}
