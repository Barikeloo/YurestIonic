import { Injectable } from '@angular/core';

export interface LinkedRestaurant {
  uuid: string;
  name: string;
  legalName?: string;
  taxId?: string;
  email?: string;
}

@Injectable({
  providedIn: 'root',
})
export class DeviceStorageService {
  private readonly STORAGE_KEY = 'tpv_linked_restaurant';

  public getLinkedRestaurant(): LinkedRestaurant | null {
    const stored = localStorage.getItem(this.STORAGE_KEY);
    if (!stored) {
      return null;
    }

    try {
      return JSON.parse(stored) as LinkedRestaurant;
    } catch {
      return null;
    }
  }

  public setLinkedRestaurant(restaurant: LinkedRestaurant): void {
    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(restaurant));
  }

  public clearLinkedRestaurant(): void {
    localStorage.removeItem(this.STORAGE_KEY);
  }

  public isDeviceLinked(): boolean {
    return this.getLinkedRestaurant() !== null;
  }
}
