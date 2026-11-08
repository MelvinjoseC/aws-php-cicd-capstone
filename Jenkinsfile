pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
    }

    triggers {
        pollSCM('H/5 * * * *')
    }

    environment {
        AWS_REGION = 'us-west-2'
        AWS_ACCOUNT_ID = '752249616998'
        ECR_REPOSITORY = 'php-capstone'
        EKS_CLUSTER = 'php-capstone-eks'
        K8S_NAMESPACE = 'capstone'

        ECR_REGISTRY = "${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
        IMAGE_URI = "${ECR_REGISTRY}/${ECR_REPOSITORY}"

        KUBECONFIG = '/var/lib/jenkins/.kube/config'
    }

    stages {

        stage('Checkout Source Code') {
            steps {
                checkout scm
            }
        }

        stage('Build Docker Image') {
            steps {
                sh '''
                    set -eu
                    docker build -t ${IMAGE_URI}:${BUILD_NUMBER} .
                '''
            }
        }

        stage('Login to Amazon ECR') {
            steps {
                sh '''
                    set -eu
                    aws ecr get-login-password --region ${AWS_REGION} | \
                    docker login --username AWS --password-stdin ${ECR_REGISTRY}
                '''
            }
        }

        stage('Push Docker Image to ECR') {
            steps {
                sh '''
                    set -eu
                    docker push ${IMAGE_URI}:${BUILD_NUMBER}
                '''
            }
        }

        stage('Verify Image in ECR') {
            steps {
                sh '''
                    set -eu
                    aws ecr describe-images \
                      --repository-name ${ECR_REPOSITORY} \
                      --image-ids imageTag=${BUILD_NUMBER} \
                      --region ${AWS_REGION} \
                      --query 'imageDetails[].imageTags' \
                      --output json
                '''
            }
        }

        stage('Connect to Amazon EKS') {
            steps {
                sh '''
                    set -eu
                    aws eks update-kubeconfig \
                      --name ${EKS_CLUSTER} \
                      --region ${AWS_REGION} \
                      --kubeconfig ${KUBECONFIG}

                    kubectl get pods -n ${K8S_NAMESPACE}
                '''
            }
        }

        stage('Deploy PHP and MySQL Using Helm') {
            steps {
                withCredentials([
                    string(
                        credentialsId: 'capstone-db-password',
                        variable: 'DB_PASSWORD'
                    ),
                    string(
                        credentialsId: 'capstone-db-root-password',
                        variable: 'DB_ROOT_PASSWORD'
                    )
                ]) {
                    sh '''
                        set -eu
                        set +x
                        umask 077

                        trap 'rm -f .helm-private-values.yaml' EXIT

                        python3 - <<'PY'
import json
import os

values = {
    "mysql": {
        "password": os.environ["DB_PASSWORD"],
        "rootPassword": os.environ["DB_ROOT_PASSWORD"]
    }
}

with open(".helm-private-values.yaml", "w") as file:
    json.dump(values, file)
PY

                        helm upgrade --install capstone ./helm/php-mysql \
                          --namespace ${K8S_NAMESPACE} \
                          -f .helm-private-values.yaml \
                          --set-string image.repository=${IMAGE_URI} \
                          --set-string image.tag=${BUILD_NUMBER} \
                          --wait \
                          --timeout 12m
                    '''
                }
            }
        }

        stage('Verify Kubernetes Deployment') {
            steps {
                sh '''
                    set -eu

                    kubectl rollout status deployment/capstone-php \
                      -n ${K8S_NAMESPACE} \
                      --timeout=300s

                    kubectl rollout status deployment/capstone-mysql \
                      -n ${K8S_NAMESPACE} \
                      --timeout=300s

                    kubectl get pods,svc,pvc -n ${K8S_NAMESPACE}
                '''
            }
        }
    }

    post {
        success {
            echo 'SUCCESS: PHP application built, pushed to ECR and deployed to EKS.'
        }

        failure {
            echo 'FAILED: Check Jenkins Console Output.'
        }

        always {
            sh 'rm -f .helm-private-values.yaml || true'
        }
    }
}
