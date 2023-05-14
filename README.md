# Kubernetes-Projet-3

# Mise en place d'un Ingress pour un site PHP avec Kubernetes

L'objectif de ce guide est de vous montrer comment mettre en place un Ingress sur le port 80 pour accéder à un site PHP via l'URL `my-app`. Nous utiliserons Kubernetes et le contrôleur Traefik pour mettre en place notre Ingress.

## Prérequis

- Un cluster Kubernetes configuré et opérationnel
- `kubectl` installé et configuré pour se connecter à votre cluster
- Le contrôleur Traefik doit être installé et configuré pour votre cluster Kubernetes

## Étape 1 : Créer un déploiement pour votre application PHP

La première étape consiste à créer un déploiement pour votre application PHP. Vous pouvez utiliser un fichier YAML pour définir votre déploiement, comme dans l'exemple ci-dessous :

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: myphp-deployment
spec:
  replicas: 1
  selector:
    matchLabels:
      app: myphp
  template:
    metadata:
      labels:
        app: myphp
    spec:
      containers:
        - name: myphp
          image: php:7.4-apache
          ports:
            - containerPort: 80
          volumeMounts:
            - name: php-app
              mountPath: /var/www/html
      volumes:
        - name: php-app
          configMap:
            name: myphp-config
```

Dans cet exemple, nous créons un déploiement nommé `myphp-deployment` pour exécuter une application PHP. Nous utilisons l'image `php:7.4-apache` pour exécuter PHP avec Apache, et nous montons un volume pour inclure les fichiers PHP de notre application.

## Étape 2 : Créer un service pour votre déploiement

Une fois que vous avez créé votre déploiement, la prochaine étape consiste à créer un service pour votre déploiement. Le service sera utilisé pour exposer votre déploiement sur le cluster Kubernetes. Vous pouvez utiliser un fichier YAML pour définir votre service, comme dans l'exemple ci-dessous :

```yaml
apiVersion: v1
kind: Service
metadata:
  name: myphp-service
spec:
  selector:
    app: myphp
  ports:
    - name: http
      port: 80
      targetPort: 80
```

Dans cet exemple, nous créons un service nommé `myphp-service` pour exposer notre déploiement sur le port 80. Nous utilisons le label `app: myphp` pour sélectionner le déploiement que nous voulons exposer.

## Étape 3 : Créer un Ingress pour votre service

La dernière étape consiste à créer un Ingress pour votre service. L'Ingress sera utilisé pour rediriger les demandes vers votre service en fonction de l'URL demandée. Vous pouvez utiliser un fichier YAML pour définir votre Ingress, comme dans l'exemple ci-dessous :

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: myphp-ingress
  annotations:
    traefik.ingress.kubernetes.io/router.entrypoints: web
spec:
 
```

## Mise en place d'un Ingress sur le port 80

L'objectif de ce tutoriel est de vous expliquer comment mettre en place un Ingress sur le port 80 de votre cluster Kubernetes afin de permettre l'accès à un site PHP en tapant simplement "my-app" comme URL.

### Prérequis

- Un cluster Kubernetes déjà configuré
- Les outils `kubectl` et `helm` installés

### Installation de Traefik

Nous allons utiliser le contrôleur d'Ingress Traefik pour mettre en place notre Ingress.

Pour cela, nous allons installer Traefik avec Helm en utilisant les commandes suivantes :

```bash
helm repo add traefik https://helm.traefik.io/traefik
helm repo update
helm install traefik traefik/traefik
```

Une fois que Traefik est installé, vous pouvez vérifier qu'il est en cours d'exécution en utilisant la commande suivante :

```bash
kubectl get pods -n kube-system
```

Vous devriez voir un pod Traefik en cours d'exécution.

### Déploiement d'une application PHP

Nous allons maintenant déployer une application PHP de démonstration à l'aide d'un fichier YAML. Vous pouvez utiliser le fichier suivant :

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: myphp-deployment
  labels:
    app: myphp
spec:
  replicas: 1
  selector:
    matchLabels:
      app: myphp
  template:
    metadata:
      labels:
        app: myphp
    spec:
      containers:
        - name: myphp
          image: k8s.gcr.io/php-apache
          ports:
            - containerPort: 80
```

Appliquez ce fichier en utilisant la commande suivante :

```bash
kubectl apply -f myphp-deployment.yaml
```

Vous devriez voir un pod en cours d'exécution en utilisant la commande suivante :

```bash
kubectl get pods
```

### Déploiement de l'Ingress

Nous allons maintenant déployer notre Ingress.

Créez un fichier YAML appelé `myphp-ingress.yaml` avec le contenu suivant :

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: myphp-ingress
  annotations:
    traefik.ingress.kubernetes.io/router.entrypoints: web
spec:
  rules:
    - host: my-app
      http:
        paths:
          - path: /
            pathType: Prefix
            backend:
              service:
                name: myphp-deployment
                port:
                  name: http
```

Appliquez ce fichier en utilisant la commande suivante :

```bash
kubectl apply -f myphp-ingress.yaml
```

Vous pouvez vérifier que votre Ingress est correctement configuré en utilisant la commande suivante :

```bash
kubectl get ingress
```

Vous devriez voir votre Ingress apparaître dans la liste.

### Accès à l'application

Une fois que votre Ingress est en cours d'exécution, vous pouvez accéder à votre application en utilisant l'URL `http://my-app`.

### Conclusion

Félicitations ! Vous avez configuré avec succès un Ingress sur le port 80 de votre cluster Kubernetes en utilisant Traefik. Vous pouvez maintenant utiliser cette méthode pour permettre l'accès à d'autres applications sur votre cluster
