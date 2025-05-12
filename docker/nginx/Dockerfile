FROM nginx:alpine

# Clear existing config
RUN rm -rf /etc/nginx/conf.d/* && rm -rf /var/www/*

# Create directories
RUN mkdir -p /var/log/nginx /var/www/html

# Copy configuration files
COPY nginx.conf /etc/nginx/nginx.conf
COPY conf.d/ /etc/nginx/conf.d/

# Fix ownership and permissions for nginx user
RUN chown -R nginx:nginx /var/www/html && \
    chown -R nginx:nginx /var/log/nginx && \
    chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Run as the nginx user for better security
CMD ["nginx", "-g", "daemon off;"]
