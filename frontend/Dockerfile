FROM node:18-alpine

# Create app directory
WORKDIR /app

# Install app dependencies
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy app source code
COPY . .

# Create public directory if it doesn't exist
RUN mkdir -p public

# Copy TinyMCE assets to public directory
RUN node copy-tinymce.js

# Expose port
EXPOSE 8080

# Start the app
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
