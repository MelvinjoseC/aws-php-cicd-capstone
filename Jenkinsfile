pipeline {
  agent any
  options { timestamps(); disableConcurrentBuilds() }
  triggers { pollSCM('H/5 * * * *') }
  environment {
    AWS_REGION = 'us-east-2'
    ECR_REPOSITORY = 'php-capstone'
    EKS_CLUSTER = 'php-capstone-eks'
  }
  stages {
    stage('Checkout') { steps { checkout scm } }
    stage('Build and Push ECR Image') {
      steps {
        sh '''
          set -eu
          ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
          REGISTRY="$ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com"
          IMAGE="$REGISTRY/$ECR_REPOSITORY:$BUILD_NUMBER"
          aws ecr get-login-password --region "$AWS_REGION" | docker login --username AWS --password-stdin "$REGISTRY"
          docker build -t "$IMAGE" .
          docker push "$IMAGE"
        '''
      }
    }
    stage('Deploy via Helm') {
      steps {
        withCredentials([
          string(credentialsId: 'capstone-db-password', variable: 'DB_PASSWORD'),
          string(credentialsId: 'capstone-db-root-password', variable: 'DB_ROOT_PASSWORD')
        ]) {
          sh '''
            set -eu
            ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
            IMAGE_REPO="$ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com/$ECR_REPOSITORY"
            aws eks update-kubeconfig --name "$EKS_CLUSTER" --region "$AWS_REGION"
            umask 077
            python3 -c 'import json,os; open(".helm-private-values.yaml","w").write(json.dumps({"mysql": {"password": os.environ["DB_PASSWORD"], "rootPassword": os.environ["DB_ROOT_PASSWORD"]}}))'
            trap 'rm -f .helm-private-values.yaml' EXIT
            helm upgrade --install capstone ./helm/php-mysql \
              --namespace capstone --create-namespace \
              -f .helm-private-values.yaml \
              --set-string image.repository="$IMAGE_REPO" \
              --set-string image.tag="$BUILD_NUMBER" \
              --wait --timeout 12m
            kubectl get pods,svc,pvc -n capstone
          '''
        }
      }
    }
  }
  post { always { sh 'rm -f .helm-private-values.yaml || true' } }
}
