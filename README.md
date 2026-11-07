# AWS 3-Tier PHP CI/CD Capstone Starter

GitHub -> Jenkins -> Amazon ECR -> Helm -> Amazon EKS -> Prometheus + Grafana.

- `app/`: PHP + PDO/MySQL guestbook. Save a message, reload, and confirm persistence.
- `Dockerfile`: official PHP/Apache image with the MySQL PDO extension.
- `Jenkinsfile`: Jenkins build-number image tags, ECR push, Helm upgrade, polling.
- `helm/php-mysql/`: deploys PHP Deployment/Service, MySQL Deployment/Service, Secret, ConfigMap, PVC, and gp3 StorageClass.

## Before first run

1. Install Docker, Java/Jenkins, AWS CLI v2, kubectl, Helm, and eksctl on the Jenkins EC2 host.
2. Create ECR repo `php-capstone` in `us-east-1`.
3. Create EKS cluster `php-capstone-eks` with managed EC2 worker nodes, and configure an Amazon EBS CSI driver IAM role and EKS add-on. Install the CSI driver before the pipeline.
4. Ensure the Jenkins EC2 IAM role is authorized to push to ECR, call EKS DescribeCluster, and access this Kubernetes cluster (EKS access entry/RBAC). Use least-privilege scoped permissions after bootstrapping.
5. In Jenkins credentials create Secret Text IDs `capstone-db-password` and `capstone-db-root-password` with strong DIFFERENT passwords.
6. Push this directory into your own public or private GitHub repository. Configure a Jenkins Pipeline project with 'Pipeline script from SCM', Git repository URL, branch `main`, Jenkinsfile path `Jenkinsfile`. Set up appropriate GitHub credentials for private repos.
7. Run Build Now. Polling every 5 minutes is configured in Jenkinsfile; add a GitHub webhook later for fast event-based builds.
8. Run `kubectl -n capstone port-forward service/capstone-php 8080:80` and browse `http://localhost:8080` on the machine running port-forward (SSH tunnel if needed).
9. For Prometheus/Grafana: `helm repo add prometheus-community https://prometheus-community.github.io/helm-charts && helm repo update`; `helm upgrade --install monitoring prometheus-community/kube-prometheus-stack -n monitoring --create-namespace --wait --timeout 15m`.
10. Grafana: `kubectl -n monitoring get svc` then `kubectl -n monitoring port-forward svc/monitoring-grafana 3000:80`. Retrieve admin password using `kubectl -n monitoring get secret monitoring-grafana -o jsonpath='{.data.admin-password}' | base64 --decode` on a secure terminal.

## Evidence

Capture GitHub files, full green Jenkins stages, ECR image tag `BUILD_NUMBER`, `helm list -n capstone`, `kubectl get pods,svc,pvc -n capstone`, saved message in PHP page, and a Grafana Kubernetes / Compute Resources / Namespace (Pods) dashboard.

## Security notes

- Do not commit real secrets or publish passwords in screenshots. The Helm chart generates a Kubernetes Secret from Jenkins-managed credentials, but Helm release storage may retain secret values. For real production, use External Secrets + AWS Secrets Manager and dedicated RBAC.
- `capstone-gp3` is a cluster-scoped StorageClass; use unique name per class/cluster. EBS supports single-node read-write volumes; keep MySQL at one replica for this lab.
- App/DB Services default to ClusterIP. Use `port-forward` or an authenticated Ingress; do not expose MySQL to the Internet.
- This learning sample uses simple PHP and MySQL; it is not a production-secured 3-tier system with a dedicated ingress/load-balancer tier.
- Clean up the EKS cluster after submitting to avoid ongoing charges.
