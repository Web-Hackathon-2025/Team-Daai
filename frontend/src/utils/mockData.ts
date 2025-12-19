// Mock data for UI testing without backend

export const mockProviders = [
  {
    id: 1,
    name: 'ABC Plumbing Services',
    email: 'abc@example.com',
    phone: '0300-1234567',
    category: 'plumber',
    location: 'Karachi',
    rating: 4.5,
    total_reviews: 25,
    services: [
      {
        id: 1,
        name: 'Pipe Repair',
        price: 1500,
        description: 'Quick pipe repair service',
      },
      {
        id: 2,
        name: 'Faucet Installation',
        price: 2000,
        description: 'Professional faucet installation',
      },
    ],
    availability: 'Mon-Fri 9AM-6PM',
    reviews: [
      {
        id: 1,
        customer_name: 'John Doe',
        rating: 5,
        comment: 'Excellent service! Very professional and punctual.',
        created_at: '2024-01-15',
      },
      {
        id: 2,
        customer_name: 'Jane Smith',
        rating: 4,
        comment: 'Good work, but took a bit longer than expected.',
        created_at: '2024-01-10',
      },
    ],
  },
  {
    id: 2,
    name: 'Quick Electric Solutions',
    email: 'quick@example.com',
    phone: '0300-7654321',
    category: 'electrician',
    location: 'Lahore',
    rating: 4.8,
    total_reviews: 42,
    services: [
      {
        id: 3,
        name: 'Wiring Installation',
        price: 3000,
        description: 'Complete wiring installation',
      },
      {
        id: 4,
        name: 'Switch Repair',
        price: 800,
        description: 'Switch and socket repair',
      },
    ],
    availability: 'Mon-Sat 8AM-7PM',
    reviews: [
      {
        id: 3,
        customer_name: 'Ahmed Ali',
        rating: 5,
        comment: 'Best electrician in town! Highly recommended.',
        created_at: '2024-01-12',
      },
    ],
  },
  {
    id: 3,
    name: 'Math Tutoring Center',
    email: 'tutor@example.com',
    phone: '0300-1112233',
    category: 'tutor',
    location: 'Islamabad',
    rating: 4.7,
    total_reviews: 18,
    services: [
      {
        id: 5,
        name: 'Mathematics Tutoring',
        price: 2500,
        description: 'One-on-one math tutoring sessions',
      },
    ],
    availability: 'Mon-Fri 2PM-8PM',
    reviews: [],
  },
  {
    id: 4,
    name: 'Sparkle Clean Services',
    email: 'sparkle@example.com',
    phone: '0300-9988776',
    category: 'cleaner',
    location: 'Karachi',
    rating: 4.3,
    total_reviews: 30,
    services: [
      {
        id: 6,
        name: 'Deep Cleaning',
        price: 5000,
        description: 'Complete deep cleaning service',
      },
      {
        id: 7,
        name: 'Regular Cleaning',
        price: 3000,
        description: 'Regular maintenance cleaning',
      },
    ],
    availability: 'Mon-Sat 9AM-6PM',
    reviews: [],
  },
];

export const mockRequests = [
  {
    id: 1,
    provider_id: 1,
    provider_name: 'ABC Plumbing Services',
    service_name: 'Pipe Repair',
    status: 'requested',
    requested_date: '2024-01-20',
    requested_time: '10:00',
    address: '123 Main St, Karachi',
    created_at: '2024-01-15T10:00:00Z',
  },
  {
    id: 2,
    provider_id: 2,
    provider_name: 'Quick Electric Solutions',
    service_name: 'Switch Repair',
    status: 'confirmed',
    requested_date: '2024-01-18',
    requested_time: '14:00',
    address: '456 Park Ave, Lahore',
    created_at: '2024-01-10T14:00:00Z',
  },
  {
    id: 3,
    provider_id: 1,
    provider_name: 'ABC Plumbing Services',
    service_name: 'Faucet Installation',
    status: 'completed',
    requested_date: '2024-01-05',
    requested_time: '11:00',
    address: '789 Oak St, Karachi',
    created_at: '2024-01-01T11:00:00Z',
    has_review: false,
  },
];

export const mockUser = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  role: 'customer' as const,
};

// Simulate API delay
export const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

