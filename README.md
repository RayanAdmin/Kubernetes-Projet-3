# Kubernetes-Projet-3

# Introduction

Ce projet a été réalisé pour créer une page web en PHP qui affiche le contenu d'une base de données MySQL. Pour cela, j'ai utilisé Kubernetes pour déployer un pod PHP et un pod MySQL, ainsi que deux services associés pour faciliter l'accès à l'application. Le fichier "kustomization.yaml" est utilisé pour partager le mot de passe de la base de données entre les deux pods.

À la différence du projet numéro 1 et 2, l'objectif de ce guide est de vous montrer comment mettre en place un Ingress sur le port 80 pour accéder à un site PHP via l'URL `my-app`. Nous utiliserons Kubernetes et le contrôleur Traefik pour mettre en place notre Ingress.

# Fonctionnement

Une fois que vous avez déployé les ressources Kubernetes, deux pods sont créés : un pod PHP et un pod MySQL. Le fichier "kustomization.yaml" est utilisé pour partager le mot de passe de la base de données entre les deux pods.

La page web en PHP affiche le contenu de la base de données MySQL. Sur cette page web, il y a un champ texte dans lequel vous pouvez rentrer un nom et le valider. Une fois que vous avez validé le nom, celui-ci est ajouté à la base de données et s'affiche sur la page PHP.

De plus, deux services sont créés pour faciliter l'accès à l'application :

- Le service PHP permet d'accéder à l'application PHP.
- Le service MySQL permet d'accéder à la base de données MySQL.

Par ailleurs, un répertoire est partagé entre le pod MySQL et le répertoire sur le serveur NFS. Ainsi, si le pod MySQL est supprimé et que le pod est redeployé, la base de données n'est pas supprimée car les données sont stockées sur le serveur NFS. De plus, fait d'utiliser un serveur NFS et y monter le répertoire partagé permet d'avoir accès au contenu de ce répertoire depuis n'importe quel machine sur le reseau.

De plus, un Ingress sur le port 80 de notre cluster Kubernetespour accéder à au site PHP via l'URL `my-app` est mis en place.

## Prérequis

- Un cluster Kubernetes configuré et opérationnel
- `kubectl` installé et configuré pour se connecter à votre cluster
- Le contrôleur Traefik doit être installé et configuré pour votre cluster Kubernetes

# Guide d'installation

1. Clonez ce dépôt sur votre machine locale :
```
git clone https://github.com/votre-nom/kubernetes-php-mysql.git
```

2. Accédez au répertoire du projet :
```
cd kubernetes-php-mysql
```

3. Modifiez le fichier "kustomization.yaml" pour définir le mot de passe de la base de données MySQL :
```
  literals:
  - password=redhat
```
Notez que le mot de passe par défaut est "redhat".

## Configuration des Persistent Volumes et Persistent Volume Claims

1. Créer un Persistent Volume (PV) pour le serveur NFS en utilisant le fichier suivant :

```
apiVersion: v1
kind: PersistentVolume
metadata:
  name: my-nfs-pv
spec:
  capacity:
    storage: 1Gi
  storageClassName: my-nfs-storage
  accessModes:
    - ReadWriteMany
  nfs:
    path: /home/rayan/projet_02/nfs
    server: <IP-address-of-Kubernetes-master-node>
```
Note : Remplacez `/home/rayan/projet_02/nfs` par le repertoire qui va être monté sur le pod.
Note : Remplacez `<IP-address-of-Kubernetes-master-node>` par l'adresse IP du noeud maître Kubernetes.

2. Créer un Persistent Volume Claim (PVC) pour le serveur NFS en utilisant le fichier suivant :

```
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: my-nfs-pvc
spec:
  accessModes:
    - ReadWriteMany
  storageClassName: my-nfs-storage
  resources:
    requests:
      storage: 1Gi
  volumeName: my-nfs-pv
```

## Configuration des Pods et Services

1. Créer un pod MySQL en utilisant le fichier suivant :

```
apiVersion: apps/v1
kind: Deployment
metadata:
  name: mydb-deployment
spec:
  replicas: 1
  selector:
    matchLabels:
      env: production-db
  template:
    metadata:
      name: mydb
      labels:
        env: production-db
    spec:
      securityContext:
        runAsUser: 1001
        fsGroup: 1002
      volumes:
        - name: my-nfs-storage
          persistentVolumeClaim:
            claimName: my-nfs-pvc
      containers:
      - name: database
        image: mysql:5.7
        ports:
        - containerPort: 3306
        volumeMounts:
          - name: my-nfs-storage
            mountPath: /var/lib/mysql
        env:
          - name: MYSQL_ROOT_PASSWORD
            valueFrom:
              secretKeyRef:
                name: mysql-pass
                key: password
          - name: MYSQL_DATABASE
            value: db
```

2. Créer un pod PHP en utilisant le fichier suivant :

```
apiVersion: apps/v1
kind: Deployment
metadata:
  name: myphp-deployment
spec:
  replicas: 3
  selector:
    matchLabels:
      env: production-frontend
  template:
    metadata:
      name: myfrontend-pod
      labels:
        env: production-frontend
    spec:
      volumes:
        - name: php-files
          hostPath:
            path: /home/rayan/projet_01/site
      containers:
        - name: frontend
          image: ragh19/phpproject:web_v1
          env:
            - name: MYSQL_ROOT_PASSWORD
              valueFrom:
                secretKeyRef:
                  name: mysql-pass
                  key: password
          ports:
            - containerPort: 80
          volumeMounts:
            - mountPath: /var/www/html
              name: php-files
```

3. Créer le service Mysql en utilisant le fichier suivant :

```
apiVersion: v1
kind: Service
metadata:
  name: mydb-service
spec:
  type: ClusterIP
  ports:
    - targetPort: 3306
      port: 3306
 #     nodePort: 30008
  selector:
    env: production-db
```

4. Créer le service PHP en utilisant le fichier suivant :

```
apiVersion: v1
kind: Service
metadata:
  name: myphp-service
spec:
  type: ClusterIP
  ports:
    - targetPort: 80
      port: 80
  selector:
    env: production-frontend
```

5. Créer un Ingress pour votre service PHP en utilisant le fichier suivant : 

La dernière étape consiste à créer un Ingress pour votre service. L'Ingress sera utilisé pour rediriger les demandes vers votre service en fonction de l'URL demandée. Vous pouvez utiliser un fichier YAML pour définir votre Ingress, comme dans l'exemple ci-dessous :

```
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: example-ingress
spec:
  rules:
    - host: mon-app
      http:
        paths:
          - path: /
            pathType: Prefix
            backend:
              service:
                name: myphp-service
                port:
                  number: 80
 
```



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
