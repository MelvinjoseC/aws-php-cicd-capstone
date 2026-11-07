pipeline {
    agent any

    environment {
        AWS_REGION = 'us-west-2'
        AWS_ACCOUNT_ID = '752249616998'
        ECR_REPOSITORY = 'php-capstone'
        ECR_REGISTRY = "${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"
        IMAGE_URI = "${ECR_REGISTRY}/${ECR_REPOSITORY}"
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
                    docker build -t ${IMAGE_URI}:${BUILD_NUMBER} .
                '''
            }
        }

        stage('Login to Amazon ECR') {
            steps {
                sh '''
                    aws ecr get-login-password --region ${AWS_REGION} | \
                    docker login --username AWS --password-stdin ${ECR_REGISTRY}
                '''
            }
        }

        stage('Push Docker Image to ECR') {
            steps {
                sh '''
                    docker push ${IMAGE_URI}:${BUILD_NUMBER}
                '''
            }
        }

        stage('Verify Image in ECR') {
            steps {
                sh '''
                    aws ecr describe-images \
                      --repository-name ${ECR_REPOSITORY} \
                      --image-ids imageTag=${BUILD_NUMBER} \
                      --region ${AWS_REGION}
                '''
            }
        }
    }

    post {
        success {
            echo 'SUCCESS: PHP Docker image built and pushed to Amazon ECR.'
        }

        failure {
            echo 'FAILED: Check Jenkins console output.'
        }
    }
}
