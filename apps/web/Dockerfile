FROM node:20-alpine

WORKDIR /app

# Install bun
RUN npm install -g bun

# Copy package files
COPY package*.json ./

# Remove any leftover node_modules that might cause issues
RUN rm -rf node_modules 2>/dev/null || true

# Fresh install with bun
RUN bun install

# Copy source
COPY . .

# Build the app
RUN bun run build

# Expose port
EXPOSE 3000

# Start production server
CMD ["node", ".output/server/index.mjs"]
