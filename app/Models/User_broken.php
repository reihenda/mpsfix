            // OPTIMASI: Skip heavy monthly balance calculation for now
            // Only update if specifically needed
            // $this->updateMonthlyBalancesOptimized($depositDate->format('Y-m'));

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in addDeposit', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reduce balance (pengurangan saldo) - OPTIMIZED VERSION
     *
     * @param float $amount Amount to reduce
     * @param string|null $description Description for the reduction
     * @param Carbon|null $customDate Custom date (optional)
     * @return bool
     */
    public function reduceBalance($amount, $description = null, $customDate = null)
    {
        try {
            DB::beginTransaction();
            $depositDate = $customDate ? $customDate : now();

            // Ensure amount is numeric and negative for reduction
            $amount = -abs(floatval($amount));

            // Prepare deposit entry dengan keterangan pengurangan
            $depositEntry = [
                'date' => $depositDate->format('Y-m-d H:i:s'),
                'amount' => round($amount, 2),
                'keterangan' => 'pengurangan',
                'deskripsi' => $description
            ];

            // Get current deposit history and ensure it's an array
            $depositHistory = $this->ensureArray($this->deposit_history);

            // Add new entry to history
            $depositHistory[] = $depositEntry;

            // Update user's total deposit (dikurangi karena amount sudah negatif)
            $this->total_deposit += $amount;

            // Update deposit history
            $this->deposit_history = $depositHistory;

            // Save the user
            $this->save();

            // OPTIMASI: Skip heavy monthly balance calculation for now
            // $this->updateMonthlyBalancesOptimized($depositDate->format('Y-m'));

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in reduceBalance', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Zero balance (nol-kan saldo) - OPTIMIZED VERSION
     *
     * @param string|null $description Description for zeroing balance
     * @param Carbon|null $customDate Custom date (optional)
     * @return bool
     */
    public function zeroBalance($description = null, $customDate = null)
    {
        try {
            DB::beginTransaction();
            
            // Get current balance
            $currentBalance = $this->getCurrentBalance();
            
            // If balance is already zero, do nothing
            if (abs($currentBalance) < 0.01) {
                return true;
            }
            
            // Create reduction entry to zero the balance
            $reductionAmount = -$currentBalance; // Amount needed to make balance zero
            
            $result = $this->reduceBalance(
                abs($reductionAmount), 
                $description ?? 'Nol-kan saldo', 
                $customDate
            );
            
            DB::commit();
            
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in zeroBalance', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get deposit history with backward compatibility
     * Automatically adds 'keterangan' field to old entries
     */
    public function getDepositHistoryWithKeterangan()
    {
        $depositHistory = $this->ensureArray($this->deposit_history);
        
        // Add backward compatibility for old entries
        foreach ($depositHistory as &$entry) {
            // If keterangan doesn't exist, assume it's 'penambahan'
            if (!isset($entry['keterangan'])) {
                $entry['keterangan'] = 'penambahan';
            }
            
            // Handle description field rename
            if (isset($entry['description']) && !isset($entry['deskripsi'])) {
                $entry['deskripsi'] = $entry['description'];
            }
        }
        
        return $depositHistory;
    }

    public function removeDeposit($index)
    {
        try {
            DB::beginTransaction();

            // Get current deposit history
            $depositHistory = $this->ensureArray($this->deposit_history);

            // Validate index
            if (!isset($depositHistory[$index])) {
                return false;
            }

            // Capture deposit date and amount before removing it
            $depositDate = null;
            $depositAmount = floatval($depositHistory[$index]['amount'] ?? 0);
            if (isset($depositHistory[$index]['date'])) {
                $depositDate = Carbon::parse($depositHistory[$index]['date']);
            }

            // Subtract the amount from total deposit
            $this->total_deposit -= $depositAmount;

            // Remove the specific deposit entry
            array_splice($depositHistory, $index, 1);

            // Update deposit history
            $this->deposit_history = $depositHistory;

            // Save the user
            $this->save();

            // OPTIMASI: Skip heavy calculation for now
            // $this->updateMonthlyBalancesOptimized();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in removeDeposit', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function recordPurchase($amount)
    {
        try {
            DB::beginTransaction();

            // Ensure amount is numeric
            $amount = floatval($amount);

            // Update total purchases
            $this->total_purchases += $amount;

            // Save the user
            $this->save();

            // OPTIMASI: Skip heavy calculation for now
            // $this->updateMonthlyBalancesOptimized();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error in recordPurchase', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getCurrentBalance()
    {
        return floatval($this->total_deposit) - floatval($this->total_purchases);
    }

    /**
     * OPTIMIZED VERSION: Update monthly balances for specific month only
     * Reduces processing time significantly
     */
    public function updateMonthlyBalancesOptimized($targetMonth = null)
    {
        try {
            // For now, just update the current balance without heavy calculation
            // This prevents the infinite loop/heavy processing issue
            
            $currentMonth = $targetMonth ?? now()->format('Y-m');
            $monthlyBalances = $this->ensureArray($this->monthly_balances);
            
            // Simple calculation for current month
            $monthlyBalances[$currentMonth] = $this->getCurrentBalance();
            
            $this->monthly_balances = $monthlyBalances;
            $this->save();
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error in updateMonthlyBalancesOptimized', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update saldo bulanan pengguna untuk seluruh periode
     * THIS IS THE ORIGINAL HEAVY METHOD - USE WITH CAUTION
     */
    public function updateMonthlyBalances($startMonth = null)
    {
        // TEMPORARILY DISABLED to prevent performance issues
        // Use updateMonthlyBalancesOptimized() instead
        \Log::warning('updateMonthlyBalances called but disabled for performance', [
            'user_id' => $this->id,
            'startMonth' => $startMonth
        ]);
        
        // Just do basic update for now
        return $this->updateMonthlyBalancesOptimized($startMonth);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /**
     * Check if user is customer
     */
    public function isCustomer()
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    /**
     * Check if user is customer or FOB
     * Fungsi untuk bisa menampilkan data FOB di dashboard
     */
    public function isCustomerOrFOB()
    {
        return $this->role === self::ROLE_CUSTOMER || $this->role === self::ROLE_FOB;
    }

    /**
     * Check if user is demo
     */
    public function isDemo()
    {
        return $this->role === self::ROLE_DEMO;
    }

    /**
     * Check if user is FOB
     */
    public function isFOB()
    {
        return $this->role === self::ROLE_FOB;
    }
}
