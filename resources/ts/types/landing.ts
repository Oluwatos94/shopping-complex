export interface LandingCategory {
    id: number;
    name: string;
    icon: string;
    description: string;
    vendorCount: number;
}

export interface Testimonial {
    id: number;
    customerName: string;
    rating: number;
    comment: string;
    date: string;
    avatar?: string;
}

export interface NavigationLink {
    label: string;
    href: string;
}

export interface HowItWorksStep {
    step: number;
    title: string;
    description: string;
    icon: string;
}
