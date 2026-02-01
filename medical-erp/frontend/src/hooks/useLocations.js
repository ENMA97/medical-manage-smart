import { useQuery } from '@tanstack/react-query';
import { locationService } from '../services/locationService';

export function useRegions() {
  return useQuery({
    queryKey: ['regions'],
    queryFn: () => locationService.getRegions(),
  });
}

export function useCounties(regionId) {
  return useQuery({
    queryKey: ['counties', regionId],
    queryFn: () => locationService.getCounties({ region_id: regionId }),
    enabled: !!regionId,
  });
}
