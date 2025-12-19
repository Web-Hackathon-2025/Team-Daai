# Karigar Frontend

React frontend for the Karigar hyperlocal services marketplace.

## Quick Start

### Install Dependencies
```bash
npm install
```

### Development Server
```bash
npm run dev
```

### Build for Production
```bash
npm run build
```
Output will be in `dist/` folder (upload this to cPanel)

## Environment Variables

Create `.env` file in the frontend folder:
```
VITE_API_BASE_URL=http://localhost/CosmoCon/api
```

For production, create `.env.production`:
```
VITE_API_BASE_URL=https://yourdomain.com/api
```

## Project Structure

```
src/
├── components/          # Reusable components
│   ├── common/         # Buttons, Inputs, Cards
│   ├── layout/         # Header, Footer, Navigation
│   └── ProtectedRoute.tsx
├── context/            # React Context
│   └── AuthContext.tsx # Authentication state
├── pages/              # Page components
│   ├── auth/          # Login, Register
│   ├── customer/      # Customer pages
│   └── provider/      # Provider pages
├── services/          # API services
│   └── api.ts         # API calls
├── utils/             # Helper functions
├── App.tsx            # Main app with routing
└── main.tsx           # Entry point
```

## Next Steps

1. Create Login and Register pages in `pages/auth/`
2. Create Customer Dashboard in `pages/customer/`
3. Create Provider Dashboard in `pages/provider/`
4. Add styling (Tailwind CSS recommended)
5. Connect to backend APIs

See `FRONTEND_README.md` in the root for detailed documentation.
