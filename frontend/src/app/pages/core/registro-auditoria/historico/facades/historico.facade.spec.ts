import { TestBed } from '@angular/core/testing';
import { HistoricoFacade } from './historico.facade';
import { AuditLogService } from '../../../../../services/audit-log.service';
import { ArchivedAuditStatsApi } from '../../../../../services/audit-log.service';

describe('HistoricoFacade', () => {
  let facade: HistoricoFacade;

  function mockStats(overrides: Partial<ArchivedAuditStatsApi> = {}): ArchivedAuditStatsApi {
    return {
      total: 0,
      by_category: [],
      top_users: [],
      by_anomaly_kind: [],
      monthly_breakdown: [],
      oldest_created_at: null,
      newest_created_at: null,
      ...overrides,
    };
  }

  beforeEach(() => {
    const serviceSpy = jasmine.createSpyObj('AuditLogService', [
      'getArchivedStats',
      'getLatestVerifyResult',
      'verifyChain',
      'buildExportUrl',
    ]);
    TestBed.configureTestingModule({
      providers: [
        HistoricoFacade,
        { provide: AuditLogService, useValue: serviceSpy },
      ],
    });
    facade = TestBed.inject(HistoricoFacade);
  });

  describe('categoriesBreakdown', () => {
    it('returns empty array when stats is null', () => {
      expect(facade.categoriesBreakdown()).toEqual([]);
    });

    it('returns empty array when by_category is empty', () => {
      facade.setStats(mockStats({ by_category: [] }));
      expect(facade.categoriesBreakdown()).toEqual([]);
    });

    it('maps category data with ES labels', () => {
      facade.setStats(mockStats({
        by_category: [
          { category: 'auth', count: 10 },
          { category: 'caja', count: 5 },
        ],
      }));
      const result = facade.categoriesBreakdown();
      expect(result.length).toBe(2);
      expect(result[0]).toEqual({
        key: 'auth', label: 'Acceso', count: 10, ratio: 1, displayWidth: 100,
      });
      expect(result[1]).toEqual({
        key: 'caja', label: 'Caja', count: 5, ratio: 0.5, displayWidth: 50,
      });
    });

    it('uses raw key as label when no mapping exists', () => {
      facade.setStats(mockStats({
        by_category: [{ category: 'unknown_cat', count: 3 }],
      }));
      const result = facade.categoriesBreakdown();
      expect(result[0].label).toBe('Unknown_cat');
    });

    it('computes ratio from max count', () => {
      facade.setStats(mockStats({
        by_category: [
          { category: 'auth', count: 20 },
          { category: 'order', count: 10 },
          { category: 'system', count: 5 },
        ],
      }));
      const result = facade.categoriesBreakdown();
      expect(result[0].ratio).toBe(1);
      expect(result[0].displayWidth).toBe(100);
      expect(result[1].ratio).toBe(0.5);
      expect(result[1].displayWidth).toBe(50);
      expect(result[2].ratio).toBe(0.25);
      expect(result[2].displayWidth).toBe(25);
    });

    it('ensures minimum displayWidth of 6', () => {
      facade.setStats(mockStats({
        by_category: [
          { category: 'auth', count: 100 },
          { category: 'system', count: 1 },
        ],
      }));
      const result = facade.categoriesBreakdown();
      expect(result[1].displayWidth).toBe(6);
    });
  });

  describe('topUsers', () => {
    it('returns empty array when stats is null', () => {
      expect(facade.topUsers()).toEqual([]);
    });

    it('maps user data with initials', () => {
      facade.setStats(mockStats({
        top_users: [
          { uuid: 'u1', name: 'Juan Pérez', role: 'admin', count: 15 },
          { uuid: 'u2', name: 'María', role: 'cashier', count: 8 },
        ],
      }));
      const result = facade.topUsers();
      expect(result.length).toBe(2);
      expect(result[0]).toEqual({
        uuid: 'u1', name: 'Juan Pérez', role: 'admin', count: 15, initials: 'JP',
      });
      expect(result[1]).toEqual({
        uuid: 'u2', name: 'María', role: 'cashier', count: 8, initials: 'MA',
      });
    });

    it('handles single-word names for initials', () => {
      facade.setStats(mockStats({
        top_users: [
          { uuid: 'u3', name: 'Ana', role: null, count: 5 },
        ],
      }));
      const result = facade.topUsers();
      expect(result[0].initials).toBe('AN');
    });

    it('preserves null role', () => {
      facade.setStats(mockStats({
        top_users: [
          { uuid: 'u4', name: 'Test User', role: null, count: 3 },
        ],
      }));
      expect(facade.topUsers()[0].role).toBeNull();
    });
  });

  describe('anomalies', () => {
    it('returns empty array when stats is null', () => {
      expect(facade.anomalies()).toEqual([]);
    });

    it('maps anomaly kinds with ES labels', () => {
      facade.setStats(mockStats({
        by_anomaly_kind: [
          { kind: 'auth_failed_burst', count: 7 },
          { kind: 'caja_mismatch', count: 3 },
        ],
      }));
      const result = facade.anomalies();
      expect(result).toEqual([
        { kind: 'auth_failed_burst', label: 'Burst de PIN fallidos', count: 7 },
        { kind: 'caja_mismatch', label: 'Cierre de caja con descuadre', count: 3 },
      ]);
    });

    it('uses raw kind as fallback label', () => {
      facade.setStats(mockStats({
        by_anomaly_kind: [
          { kind: 'unknown_anomaly', count: 1 },
        ],
      }));
      expect(facade.anomalies()[0].label).toBe('unknown_anomaly');
    });
  });

  describe('totalAnomalies', () => {
    it('returns 0 when stats is null', () => {
      expect(facade.totalAnomalies()).toBe(0);
    });

    it('sums anomaly counts', () => {
      facade.setStats(mockStats({
        by_anomaly_kind: [
          { kind: 'auth_failed_burst', count: 7 },
          { kind: 'caja_mismatch', count: 3 },
          { kind: 'other', count: 2 },
        ],
      }));
      expect(facade.totalAnomalies()).toBe(12);
    });

    it('returns 0 when no anomalies', () => {
      facade.setStats(mockStats({ by_anomaly_kind: [] }));
      expect(facade.totalAnomalies()).toBe(0);
    });
  });

  describe('monthlyPoints', () => {
    it('returns empty array when stats is null', () => {
      expect(facade.monthlyPoints()).toEqual([]);
    });

    it('computes ratio and displayHeight from max', () => {
      facade.setStats(mockStats({
        monthly_breakdown: [
          { month: '2026-01', count: 50 },
          { month: '2026-02', count: 25 },
          { month: '2026-03', count: 100 },
        ],
      }));
      const result = facade.monthlyPoints();
      expect(result.length).toBe(3);
      expect(result[0]).toEqual({
        key: '2026-01', label: 'ene 26', count: 50, ratio: 0.5, displayHeight: 50,
      });
      expect(result[1]).toEqual({
        key: '2026-02', label: 'feb 26', count: 25, ratio: 0.25, displayHeight: 25,
      });
      expect(result[2]).toEqual({
        key: '2026-03', label: 'mar 26', count: 100, ratio: 1, displayHeight: 100,
      });
    });

    it('ensures minimum displayHeight of 4', () => {
      facade.setStats(mockStats({
        monthly_breakdown: [
          { month: '2026-01', count: 1000 },
          { month: '2026-02', count: 1 },
        ],
      }));
      const result = facade.monthlyPoints();
      expect(result[1].displayHeight).toBe(4);
    });
  });

  describe('peakMonth', () => {
    it('returns null when no points', () => {
      expect(facade.peakMonth()).toBeNull();
    });

    it('returns the month with highest count', () => {
      facade.setStats(mockStats({
        monthly_breakdown: [
          { month: '2026-01', count: 10 },
          { month: '2026-02', count: 50 },
          { month: '2026-03', count: 30 },
        ],
      }));
      const peak = facade.peakMonth();
      expect(peak).toEqual({ key: '2026-02', label: 'feb 26', count: 50 });
    });
  });

  describe('monthlyAverage', () => {
    it('returns 0 when no monthly points', () => {
      facade.setStats(mockStats({ total: 100 }));
      expect(facade.monthlyAverage()).toBe(0);
    });

    it('computes total / number of months', () => {
      facade.setStats(mockStats({
        total: 300,
        monthly_breakdown: [
          { month: '2026-01', count: 100 },
          { month: '2026-02', count: 100 },
          { month: '2026-03', count: 100 },
        ],
      }));
      expect(facade.monthlyAverage()).toBe(100);
    });

    it('rounds to integer', () => {
      facade.setStats(mockStats({
        total: 10,
        monthly_breakdown: [
          { month: '2026-01', count: 10 },
          { month: '2026-02', count: 0 },
          { month: '2026-03', count: 0 },
        ],
      }));
      expect(facade.monthlyAverage()).toBe(3);
    });
  });

  describe('hasData', () => {
    it('returns false when stats is null', () => {
      expect(facade.hasData()).toBeFalse();
    });

    it('returns false when total is 0', () => {
      facade.setStats(mockStats({ total: 0 }));
      expect(facade.hasData()).toBeFalse();
    });

    it('returns true when total > 0', () => {
      facade.setStats(mockStats({ total: 42 }));
      expect(facade.hasData()).toBeTrue();
    });
  });
});
