FROM node:20-alpine

WORKDIR /app

# Copy package files
COPY package*.json ./

# Remove any leftover lockfiles or modules that might cause issues
RUN rm -rf node_modules package-lock.json 2>/dev/null || true

# Fresh install
RUN npm install

# Copy source
COPY . .

# Expose port
EXPOSE 3000

# Start Nuxt dev
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
