FROM node:22-alpine

WORKDIR /app

# Install dependencies
COPY package*.json ./
RUN npm ci

# Copy source
COPY . .

# Build for production
RUN npm run build

EXPOSE 3000

# Serve with vite preview (reads PORT env var)
CMD ["npm", "run", "preview"]
