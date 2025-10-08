#!/bin/bash

# Script pour refactoriser les contrôleurs
controllers=(
    "src/Controller/CertificationEditController.php"
    "src/Controller/ProjectEditController.php" 
    "src/Controller/VolunteeringEditController.php"
    "src/Controller/ProfileEditController.php"
    "src/Controller/PostEditController.php"
)

for controller in "${controllers[@]}"; do
    if [ -f "$controller" ]; then
        echo "Refactoring $controller..."
        
        # Ajouter ProfileRepository dans les imports s'il n'existe pas
        if ! grep -q "use App\\\\Repository\\\\ProfileRepository;" "$controller"; then
            sed -i '/use Doctrine\\ORM\\EntityManagerInterface;/a use App\\Repository\\ProfileRepository;' "$controller"
        fi
        
        # Remplacer les signatures de méthodes pour ajouter ProfileRepository
        sed -i 's/EntityManagerInterface \$entityManager, string \$username/EntityManagerInterface $entityManager, ProfileRepository $profileRepository, string $username/g' "$controller"
        sed -i 's/string \$username, EntityManagerInterface \$entityManager/string $username, EntityManagerInterface $entityManager, ProfileRepository $profileRepository/g' "$controller"
        
        # Remplacer les requêtes complexes par l'appel au repository
        sed -i '/\$targetUser = \$entityManager->getRepository(User::class)/,/->getOneOrNullResult();/c\
        $targetUser = $profileRepository->findUserByUsername($username);' "$controller"
        
        echo "✅ $controller refactorisé"
    fi
done

echo "🎉 Tous les contrôleurs ont été refactorisés !"