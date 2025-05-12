FROM node:18-alpine

# Create app directory
WORKDIR /app

# Install app dependencies
COPY package*.json ./
RUN npm install

# Copy app source code
COPY . .

# Note: Build command commented out as we're just setting up the skeleton
# RUN npm run build

# Expose port
EXPOSE 8080

# Start the app
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
