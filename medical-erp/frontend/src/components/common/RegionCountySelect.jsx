import React from 'react';
import { useRegions, useCounties } from '../../hooks/useLocations';

export default function RegionCountySelect({
  regionId,
  countyId,
  onRegionChange,
  onCountyChange,
  errors = {},
  disabled = false,
}) {
  const { data: regionsData, isLoading: regionsLoading } = useRegions();
  const { data: countiesData, isLoading: countiesLoading } = useCounties(regionId);

  const regions = regionsData?.data || [];
  const counties = countiesData?.data || [];

  const handleRegionChange = (e) => {
    const value = e.target.value;
    onRegionChange(value);
    onCountyChange('');
  };

  const selectClass = (hasError) =>
    `block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 ${
      hasError
        ? 'border-red-300 focus:border-red-500'
        : 'border-gray-300 focus:border-primary-500'
    } ${disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'}`;

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label htmlFor="region_id" className="block text-sm font-medium text-gray-700 mb-1">
          المنطقة <span className="text-xs text-gray-400">(Region)</span>
        </label>
        <select
          id="region_id"
          value={regionId || ''}
          onChange={handleRegionChange}
          disabled={disabled || regionsLoading}
          className={selectClass(errors.region_id)}
        >
          <option value="">
            {regionsLoading ? 'جاري التحميل...' : '-- اختر المنطقة --'}
          </option>
          {regions.map((region) => (
            <option key={region.id} value={region.id}>
              {region.name_ar} - {region.name}
            </option>
          ))}
        </select>
        {errors.region_id && (
          <p className="mt-1 text-sm text-red-600">{errors.region_id}</p>
        )}
      </div>

      <div>
        <label htmlFor="county_id" className="block text-sm font-medium text-gray-700 mb-1">
          المحافظة <span className="text-xs text-gray-400">(County)</span>
        </label>
        <select
          id="county_id"
          value={countyId || ''}
          onChange={(e) => onCountyChange(e.target.value)}
          disabled={disabled || !regionId || countiesLoading}
          className={selectClass(errors.county_id)}
        >
          <option value="">
            {!regionId
              ? '-- اختر المنطقة أولاً --'
              : countiesLoading
                ? 'جاري التحميل...'
                : '-- اختر المحافظة --'}
          </option>
          {counties.map((county) => (
            <option key={county.id} value={county.id}>
              {county.name_ar} - {county.name}
            </option>
          ))}
        </select>
        {errors.county_id && (
          <p className="mt-1 text-sm text-red-600">{errors.county_id}</p>
        )}
      </div>
    </div>
  );
}
