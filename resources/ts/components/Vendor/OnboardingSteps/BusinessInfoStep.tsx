import { BusinessInfo } from '@/hooks/useOnboarding';

interface BusinessInfoStepProps {
    data: BusinessInfo;
    errors: Partial<Record<keyof BusinessInfo, string>>;
    categories: { id: number; name: string; slug: string }[];
    onUpdate: (field: keyof BusinessInfo, value: string) => void;
}

export default function BusinessInfoStep({ data, errors, categories, onUpdate }: BusinessInfoStepProps) {
    return (
        <div className="space-y-6">
            <div>
                <h2 className="text-2xl font-serif font-bold text-gray-900">Business Information</h2>
                <p className="mt-1 text-gray-500">Tell us about your business</p>
            </div>

            <div className="space-y-5">
                {/* Legal Entity Name */}
                <div>
                    <label htmlFor="legal_entity_name" className="block text-sm font-medium text-gray-900 mb-2">
                        Legal Entity Name
                    </label>
                    <input
                        type="text"
                        id="legal_entity_name"
                        value={data.legal_entity_name}
                        onChange={(e) => onUpdate('legal_entity_name', e.target.value)}
                        placeholder="Baking Corner Ng"
                        className={`
                            w-full px-4 py-3 border rounded-lg text-gray-900 placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent
                            transition-colors
                            ${errors.legal_entity_name ? 'border-red-500' : 'border-gray-300'}
                        `}
                    />
                    {errors.legal_entity_name && (
                        <p className="mt-1.5 text-sm text-red-600">{errors.legal_entity_name}</p>
                    )}
                </div>

                {/* Business Category */}
                <div>
                    <label htmlFor="business_category" className="block text-sm font-medium text-gray-900 mb-2">
                        Business Category
                    </label>
                    <select
                        id="business_category"
                        value={data.business_category}
                        onChange={(e) => onUpdate('business_category', e.target.value)}
                        className={`
                            w-full px-4 py-3 border rounded-lg text-gray-900 bg-white
                            focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent
                            transition-colors appearance-none
                            ${!data.business_category ? 'text-gray-400' : ''}
                            ${errors.business_category ? 'border-red-500' : 'border-gray-300'}
                        `}
                    >
                        <option value="">Select a category</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.slug}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                    {errors.business_category && (
                        <p className="mt-1.5 text-sm text-red-600">{errors.business_category}</p>
                    )}
                </div>

                {/* Tax Identification Number */}
                <div>
                    <label htmlFor="tax_identification_number" className="block text-sm font-medium text-gray-900 mb-2">
                        Tax Identification Number (TIN)
                    </label>
                    <input
                        type="text"
                        id="tax_identification_number"
                        value={data.tax_identification_number}
                        onChange={(e) => onUpdate('tax_identification_number', e.target.value)}
                        placeholder="12-34567890"
                        className={`
                            w-full px-4 py-3 border rounded-lg text-gray-900 placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent
                            transition-colors
                            ${errors.tax_identification_number ? 'border-red-500' : 'border-gray-300'}
                        `}
                    />
                    {errors.tax_identification_number && (
                        <p className="mt-1.5 text-sm text-red-600">{errors.tax_identification_number}</p>
                    )}
                </div>

                {/* Physical Address */}
                <div>
                    <label htmlFor="physical_address" className="block text-sm font-medium text-gray-900 mb-2">
                        Physical Address
                    </label>
                    <textarea
                        id="physical_address"
                        value={data.physical_address}
                        onChange={(e) => onUpdate('physical_address', e.target.value)}
                        placeholder="Hausawa Layout, Lagos State"
                        rows={3}
                        className={`
                            w-full px-4 py-3 border rounded-lg text-gray-900 placeholder-gray-400
                            focus:outline-none focus:ring-2 focus:ring-primary-olive focus:border-transparent
                            transition-colors resize-none
                            ${errors.physical_address ? 'border-red-500' : 'border-gray-300'}
                        `}
                    />
                    {errors.physical_address && (
                        <p className="mt-1.5 text-sm text-red-600">{errors.physical_address}</p>
                    )}
                </div>
            </div>
        </div>
    );
}
